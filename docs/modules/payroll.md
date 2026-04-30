# Payroll Module

Modul payroll saat ini difokuskan pada kesiapan arsitektur:

- `Models/` menyimpan model Eloquent untuk payroll, BPJS, tax result, dan payslip.
- `Queries/PayrollOverviewQuery.php` menyediakan ringkasan untuk dashboard dan halaman payroll.
- `Actions/CalculatePayrollPeriod.php` dan `Services/PayrollCalculationService.php` adalah titik masuk use case perhitungan.
- `DTOs/PayrollPeriodSnapshotData.php` menyiapkan kontrak data hasil kalkulasi.
- `Enums/` menjaga status period dan run tetap konsisten.

## Prinsip yang dipakai

- Semua angka finansial memakai `decimal`.
- Histori payroll disimpan sebagai snapshot per periode.
- Rule BPJS dan tax dipisah dari hasil kalkulasi.
- API dan web diarahkan untuk berbagi layer domain yang sama.
