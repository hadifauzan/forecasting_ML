# Rencana Implementasi: Perbaikan Kalkulasi & Agregasi Regresi Linear

Dokumen ini menjelaskan analisis masalah dan rencana perbaikan untuk mengatasi tingginya nilai error (MAE, RMSE, MAPE) pada metode **Regresi Linear** dibandingkan dengan **ARIMA**.

## Analisis Masalah (Diagnosis)

Setelah menganalisis data riil dari database melalui debug route `/temp-debug-lr`, kami menemukan **mismatch logika yang sangat fatal** pada proses kalkulasi Regresi Linear saat memilih filter **"Semua Produk" (Keseluruhan)**:

1. **Perbedaan Garis Waktu Produk**: Masing-masing produk memiliki jumlah data histori (training) dan periode pengujian (testing/actual) yang berbeda-beda.
2. **Overlap Status pada Tanggal yang Sama**: 
   - Pada tanggal tertentu (misalnya `2026-01-15`), Produk A sudah masuk periode pengujian (`data_type = 'actual'`), sedangkan Produk B masih berada di periode pelatihan (`data_type = 'training'`).
3. **Mismatched Aggregation (Bug Utama)**:
   - Ketika menghitung Regresi Linear untuk "Semua Produk", sistem melakukan `SUM(actual_sales)` yang dikelompokkan berdasarkan `date` dan `data_type`.
   - Akibatnya, pada tanggal overlap seperti `2026-01-15`:
     - Baris `training` menjumlahkan data training Produk B (bernilai besar, ~358).
     - Baris `actual` menjumlahkan data actual Produk A saja (bernilai sangat kecil, ~5).
   - Sistem mem-fitting model Regresi Linear makro pada total data training (yang memprediksi tren total berkisar ~400-500).
   - Namun, saat menghitung error pada periode `actual`, untuk tanggal overlap, sistem membandingkan prediksi total Regresi Linear (~434) dengan actual sales Produk A saja (~5).
   - Ini menghasilkan error harian yang sangat besar (`434 - 5 = 429`), sehingga mendistorsi nilai **MAE menjadi 328.3979** dan **MAPE menjadi 4072.37%**.
   - Sementara itu, ARIMA tidak memiliki masalah ini karena ARIMA dihitung *per produk* terlebih dahulu di Python, disimpan di database, lalu baru dijumlahkan di query SQL.

## Solusi yang Diusulkan

Untuk menyelaraskan perhitungan Regresi Linear dengan ARIMA secara adil dan akurat, kita harus mengubah kalkulasi Regresi Linear menjadi **per produk** terlebih dahulu sebelum dilakukan agregasi:

1. **Ambil Data Detail Per Produk**: Query semua data dari `arima_forecast_details` tanpa agregasi SQL awal.
2. **Kalkulasi Regresi Linear Per Produk**:
   - Kelompokkan data berdasarkan `produk`.
   - Untuk setiap produk, lakukan fitting formula $y = mx + c$ pada data training produk tersebut.
   - Hitung prediksi Regresi Linear (`lr_pred`) untuk setiap baris data produk tersebut (training, actual, forecast) menggunakan indeks hari lokal produk tersebut.
3. **Agregasi Hasil**:
   - Setelah semua baris individual memiliki nilai `lr_pred` yang akurat, lakukan pengelompokkan (grouping) secara PHP/Collection berdasarkan `date` dan `data_type`.
   - Jumlahkan `actual_sales`, `predicted_sales` (ARIMA), dan `lr_pred` (Regresi Linear) untuk semua produk pada tanggal dan tipe data yang sama.
4. **Hitung Metrik Evaluasi**:
   - Hitung MAE, RMSE, dan MAPE berdasarkan data yang telah diagregasikan secara presisi.

---

## Perubahan Kode yang Diusulkan

### [Component] Backend Controller

#### [MODIFY] [ModelEvaluationController.php](file:///c:/Users/LEGION/Documents/GitHub/skripsi_forecasting22/app/Http/Controllers/Admin/ModelEvaluationController.php)

Kita akan mengubah fungsi `getComparisonData($produk)` untuk mengimplementasikan algoritma di atas:

```php
    public function getComparisonData($produk)
    {
        try {
            $isAll = $produk === 'all';

            // 1. Ambil data detail tanpa agregasi awal agar bisa dihitung per produk
            if ($isAll) {
                $details = DB::table('arima_forecast_details')
                    ->orderBy('date', 'asc')
                    ->get();
            } else {
                $details = DB::table('arima_forecast_details')
                    ->where('produk', $produk)
                    ->orderBy('date', 'asc')
                    ->get();
            }

            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data evaluasi di database.'
                ], 404);
            }

            // 2. Kelompokkan berdasarkan produk dan hitung Regresi Linear lokal per produk
            $groupedByProduct = $details->groupBy('produk');
            $calculatedDetails = collect();

            foreach ($groupedByProduct as $prodCode => $prodRows) {
                // Urutkan berdasarkan tanggal
                $sortedRows = $prodRows->sortBy('date')->values();

                $trainingData = $sortedRows->filter(fn($d) => $d->data_type === 'training')->values();
                $actualData = $sortedRows->filter(fn($d) => $d->data_type === 'actual')->values();
                $forecastData = $sortedRows->filter(fn($d) => $d->data_type === 'forecast')->values();

                $n = count($trainingData);
                
                // Fitting Linear Regression (y = mx + c) untuk produk ini
                $m = 0.0;
                $c = 0.0;

                if ($n > 0) {
                    $sumX = 0;
                    $sumY = 0;
                    $sumXY = 0;
                    $sumXX = 0;

                    for ($i = 0; $i < $n; $i++) {
                        $x = $i + 1;
                        $y = (float) $trainingData[$i]->actual_sales;

                        $sumX += $x;
                        $sumY += $y;
                        $sumXY += $x * $y;
                        $sumXX += $x * $x;
                    }

                    $denominator = $n * $sumXX - $sumX * $sumX;
                    if ($denominator != 0) {
                        $m = ($n * $sumXY - $sumX * $sumY) / $denominator;
                        $c = ($sumY - $m * $sumX) / $n;
                    } else {
                        $c = $sumY / $n;
                    }
                }

                // Hitung lr_pred untuk setiap baris data produk ini
                $dayIndex = 1;
                foreach ($sortedRows as $row) {
                    $row->lr_pred = max(0.0, $m * $dayIndex + $c);
                    $calculatedDetails->push($row);
                    $dayIndex++;
                }
            }

            // 3. Agregasikan data per tanggal dan tipe data
            $aggregated = $calculatedDetails->groupBy(function($item) {
                return $item->date . '_' . $item->data_type;
            })->map(function($group) {
                $first = $group->first();
                return (object)[
                    'date' => $first->date,
                    'data_type' => $first->data_type,
                    'actual_sales' => $group->sum('actual_sales'),
                    'predicted_sales' => $group->sum('predicted_sales'),
                    'lr_pred' => $group->sum('lr_pred')
                ];
            })->values()->sortBy('date')->values();

            // 4. Bagi baris agregasi berdasarkan data_type
            $trainingAgg = $aggregated->filter(fn($d) => $d->data_type === 'training')->values();
            $actualAgg = $aggregated->filter(fn($d) => $d->data_type === 'actual')->values();
            $forecastAgg = $aggregated->filter(fn($d) => $d->data_type === 'forecast')->values();

            // 5. Susun data chart dan table
            $trainingChart = [];
            foreach ($trainingAgg as $row) {
                $trainingChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => 0.0,
                    'lr_pred' => round($row->lr_pred, 4)
                ];
            }

            $actualChart = [];
            $tableData = [];
            foreach ($actualAgg as $row) {
                $actualChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => (float) $row->predicted_sales,
                    'lr_pred' => round($row->lr_pred, 4)
                ];

                $arimaError = (float) $row->actual_sales - (float) $row->predicted_sales;
                $lrError = (float) $row->actual_sales - (float) $row->lr_pred;

                $tableData[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => (float) $row->predicted_sales,
                    'arima_error' => round($arimaError, 4),
                    'arima_abs_error' => abs(round($arimaError, 4)),
                    'lr_pred' => round($row->lr_pred, 4),
                    'lr_error' => round($lrError, 4),
                    'lr_abs_error' => abs(round($lrError, 4)),
                ];
            }

            $forecastChart = [];
            foreach ($forecastAgg as $row) {
                $forecastChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'arima_pred' => (float) $row->predicted_sales,
                    'lr_pred' => round($row->lr_pred, 4)
                ];
            }

            // 6. Kalkulasi Metrik Akurasi Agregat (MAE, RMSE, MAPE)
            $k = count($actualAgg);
            $arimaMae = 0.0; $arimaRmse = 0.0; $arimaMape = 0.0;
            $lrMae = 0.0; $lrRmse = 0.0; $lrMape = 0.0;

            if ($k > 0) {
                $arimaSumAbsErr = 0.0; $arimaSumSqErr = 0.0; $arimaSumPctErr = 0.0;
                $lrSumAbsErr = 0.0; $lrSumSqErr = 0.0; $lrSumPctErr = 0.0;

                foreach ($actualAgg as $row) {
                    $act = (float) $row->actual_sales;
                    $arima = (float) $row->predicted_sales;
                    $lr = (float) $row->lr_pred;

                    // ARIMA
                    $arimaErr = $act - $arima;
                    $arimaSumAbsErr += abs($arimaErr);
                    $arimaSumSqErr += $arimaErr * $arimaErr;
                    $denom = $act == 0.0 ? 1.0 : $act;
                    $arimaSumPctErr += abs($arimaErr) / $denom;

                    // Regresi Linear
                    $lrErr = $act - $lr;
                    $lrSumAbsErr += abs($lrErr);
                    $lrSumSqErr += $lrErr * $lrErr;
                    $lrSumPctErr += abs($lrErr) / $denom;
                }

                $arimaMae = $arimaSumAbsErr / $k;
                $arimaRmse = sqrt($arimaSumSqErr / $k);
                $arimaMape = ($arimaSumPctErr / $k) * 100.0;

                $lrMae = $lrSumAbsErr / $k;
                $lrRmse = sqrt($lrSumSqErr / $k);
                $lrMape = ($lrSumPctErr / $k) * 100.0;
            }

            // 7. Siapkan metadata produk
            $productInfo = [
                'produk' => $isAll ? 'Semua Produk' : $produk,
                'name_item' => $isAll ? 'Keseluruhan Penjualan' : 'Produk Individu',
                'category' => $isAll ? 'Semua Kategori' : '-',
            ];

            if (!$isAll) {
                $itemInfo = DB::table('master_items')
                    ->leftJoin('master_items_categories', 'master_items.item_id', '=', 'master_items_categories.item_id')
                    ->leftJoin('master_categories', 'master_items_categories.categories_id', '=', 'master_categories.category_id')
                    ->where('master_items.code_item', $produk)
                    ->select('master_items.name_item', 'master_categories.name_category')
                    ->first();
                
                if ($itemInfo) {
                    $productInfo['name_item'] = $itemInfo->name_item;
                    $productInfo['category'] = $itemInfo->name_category ?? '-';
                }
            }

            return response()->json([
                'success' => true,
                'summary' => [
                    'produk' => $productInfo['produk'],
                    'name_item' => $productInfo['name_item'],
                    'category' => $productInfo['category'],
                    'arima' => [
                        'mae' => round($arimaMae, 4),
                        'rmse' => round($arimaRmse, 4),
                        'mape' => round($arimaMape, 2),
                    ],
                    'linear_regression' => [
                        'mae' => round($lrMae, 4),
                        'rmse' => round($lrRmse, 4),
                        'mape' => round($lrMape, 2),
                        'slope' => $isAll ? 0.0 : round($m, 4), // Slope makro kurang bermakna untuk gabungan
                        'intercept' => $isAll ? 0.0 : round($c, 4),
                    ]
                ],
                'chart_data' => [
                    'training' => $trainingChart,
                    'actual' => $actualChart,
                    'forecast' => $forecastChart,
                ],
                'table_data' => $tableData,
            ]);

        } catch (\Exception $e) {
            Log::error("Error on getComparisonData: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }
```

---

## Rencana Verifikasi

### Pengujian Manual
1. Panggil route `/temp-debug-lr` menggunakan `read_url_content`.
2. Verifikasi apakah nilai MAE, RMSE, dan MAPE untuk Regresi Linear telah turun menjadi normal dan sebanding dengan ARIMA.
3. Pastikan tidak ada data yang bernilai `NaN` atau pembagian dengan nol.
4. Hapus route `/temp-debug-lr` setelah pengujian berhasil untuk menjaga keamanan aplikasi.
