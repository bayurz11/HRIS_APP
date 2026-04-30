# HARIS Implementation Notes

Implementasi awal ini menerjemahkan blueprint `README.md` menjadi fondasi proyek yang bisa langsung dikembangkan:

- Laravel 13 + Livewire starter kit sudah discaffold di root proyek.
- Autoload `Modules\\` sudah aktif agar domain dapat dipisah dari `app/`.
- Route web dipisah ke `routes/modules/*.php`.
- API versioning dimulai dari `routes/api.php` dengan prefix `/api/v1`.
- Domain dashboard dan payroll sudah punya query layer sendiri.
- Modul placeholder tersedia untuk `Organization`, `Users`, `Workflows`, `Documents`, `Reports`, `Notifications`, dan `Audit Trail`.

## Fondasi payroll yang sudah tersedia

- Tabel organisasi: `organizations`, `departments`, `positions`, `employees`
- Tabel payroll master: `payroll_groups`, `payroll_periods`, `payroll_components`, `tax_statuses`, `bpjs_rules`
- Tabel payroll profile: `bpjs_profiles`, `employee_payroll_profiles`, `employee_payroll_components`
- Tabel snapshot payroll: `payroll_runs`, `payroll_run_items`, `payroll_bpjs_results`, `payroll_tax_results`, `payslips`

## Arah pengembangan berikutnya

1. Tambahkan seed master data untuk organization, tax status, payroll component, dan payroll group.
2. Integrasikan role-permission package seperti `spatie/laravel-permission`.
3. Bentuk halaman Livewire per domain untuk index/create/detail/approval flow.
4. Tambahkan action nyata untuk proses payroll, publishing payslip, dan audit trail.
5. Perluas endpoint API untuk payroll run, component, bpjs, tax, dan payslip.
