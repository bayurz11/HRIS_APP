# HARIS

## HR, Attendance, Payroll, Internal System

**HARIS** adalah aplikasi HRIS dan payroll berbasis SaaS untuk bisnis kecil hingga perusahaan menengah. Sistem ini dirancang menggunakan **Laravel** sebagai backend utama dan **Livewire Starter Kit** sebagai layer UI interaktif, dengan pendekatan **modular monolith** agar tetap cepat dikembangkan, mudah dipelihara, dan siap berkembang menjadi sistem enterprise.

---

## Daftar Isi

1. [Ringkasan Produk](#1-ringkasan-produk)
2. [Tujuan Arsitektur](#2-tujuan-arsitektur)
3. [Stack Utama](#3-stack-utama)
4. [Prinsip Arsitektur](#4-prinsip-arsitektur)
5. [Struktur Root Project](#5-struktur-root-project)
6. [Struktur Laravel](#6-struktur-laravel)
7. [Struktur Domain Modular](#7-struktur-domain-modular)
8. [Struktur Livewire](#8-struktur-livewire)
9. [Modul Bisnis HARIS](#9-modul-bisnis-haris)
10. [Fitur Utama Produk](#10-fitur-utama-produk)
11. [Multi-Tenant SaaS Architecture](#11-multi-tenant-saas-architecture)
12. [Role dan Permission](#12-role-dan-permission)
13. [Database Core Schema](#13-database-core-schema)
14. [Payroll, BPJS, Pajak, dan Payslip](#14-payroll-bpjs-pajak-dan-payslip)
15. [Attendance, Leave, Overtime, dan Loan](#15-attendance-leave-overtime-dan-loan)
16. [API Design](#16-api-design)
17. [Service Layer dan Action Pattern](#17-service-layer-dan-action-pattern)
18. [Workflow dan Approval](#18-workflow-dan-approval)
19. [Audit Trail](#19-audit-trail)
20. [Notification System](#20-notification-system)
21. [Reporting dan Export](#21-reporting-dan-export)
22. [Security dan Data Protection](#22-security-dan-data-protection)
23. [Payroll Edge Cases](#23-payroll-edge-cases)
24. [Attendance Edge Cases](#24-attendance-edge-cases)
25. [Testing Strategy](#25-testing-strategy)
26. [Queue, Scheduler, dan Background Jobs](#26-queue-scheduler-dan-background-jobs)
27. [Observability dan Monitoring](#27-observability-dan-monitoring)
28. [Backup, Restore, dan Disaster Recovery](#28-backup-restore-dan-disaster-recovery)
29. [CI/CD dan Deployment](#29-cicd-dan-deployment)
30. [Import, Export, dan Onboarding Data](#30-import-export-dan-onboarding-data)
31. [Data Retention dan Arsip](#31-data-retention-dan-arsip)
32. [Product Roadmap](#32-product-roadmap)
33. [Milestone Development](#33-milestone-development)
34. [Struktur Dokumentasi](#34-struktur-dokumentasi)
35. [Konvensi Penamaan](#35-konvensi-penamaan)
36. [Langkah Awal Development](#36-langkah-awal-development)
37. [Kesimpulan](#37-kesimpulan)

---

## 1. Ringkasan Produk

HARIS adalah sistem terpadu untuk mengelola:

- data karyawan
- struktur organisasi
- presensi
- shift kerja
- cuti dan izin
- lembur
- kasbon atau pinjaman karyawan
- payroll
- BPJS
- PPh 21
- slip gaji digital
- pengumuman perusahaan
- komunikasi internal
- laporan HR dan payroll
- audit trail
- workflow approval
- integrasi eksternal

Target utama HARIS adalah bisnis dengan kebutuhan operasional HR yang mulai kompleks, tetapi tetap membutuhkan sistem yang mudah digunakan.

### Target awal

- Restoran dan kafe
- Salon dan barbershop
- Klinik kecil
- Toko retail
- Bengkel
- Laundry
- Perusahaan jasa
- Bisnis multi-cabang
- Perusahaan dengan 5–300 karyawan

### Positioning

> HARIS adalah aplikasi absensi, payroll, dan HR otomatis untuk bisnis yang ingin mengelola karyawan dengan rapi tanpa sistem enterprise yang rumit.

### Tagline

> Kelola absensi, gaji, cuti, lembur, kasbon, dan slip gaji dari satu sistem.

---

## 2. Tujuan Arsitektur

Struktur ini dirancang agar HARIS:

- mudah dikembangkan oleh tim kecil maupun besar
- modular dan mudah dipelihara
- siap menangani banyak modul bisnis
- mendukung role dan permission kompleks
- mendukung multi-company atau multi-tenant
- mendukung approval flow
- mendukung audit trail
- mendukung notification system
- mendukung reporting besar
- mendukung API untuk mobile dan integrasi
- tetap nyaman dikembangkan dengan pendekatan Laravel monolith yang terstruktur

Pendekatan awal yang direkomendasikan adalah **modular monolith**, bukan microservices.

Microservices hanya perlu dipertimbangkan jika:

- jumlah tim development sudah besar
- beban traffic sangat tinggi
- modul tertentu butuh scaling terpisah
- ada kebutuhan deployment independen antar service
- kompleksitas bisnis sudah melebihi kapasitas monolith

---

## 3. Stack Utama

| Layer           | Teknologi                                                    |
| --------------- | ------------------------------------------------------------ |
| Backend         | Laravel                                                      |
| UI Layer        | Livewire Starter Kit                                         |
| Template        | Blade                                                        |
| Styling         | Tailwind CSS                                                 |
| Frontend Build  | Vite                                                         |
| Authentication  | Laravel Auth / Starter Kit Auth                              |
| Authorization   | Policy, Gate, Spatie Permission                              |
| Database        | MySQL atau PostgreSQL                                        |
| Cache           | Redis                                                        |
| Queue           | Redis + Laravel Queue                                        |
| Queue Dashboard | Laravel Horizon                                              |
| File Storage    | Local, S3, Cloudflare R2, atau MinIO                         |
| Notification    | Email, database notification, broadcast                      |
| API Auth        | Laravel Sanctum                                              |
| Testing         | Pest atau PHPUnit                                            |
| Error Tracking  | Sentry atau Bugsnag                                          |
| Deployment      | VPS, Docker, Laravel Forge, Ploi, Envoyer, atau CI/CD custom |

### Rekomendasi database

Untuk sistem payroll dan HR yang kompleks, **PostgreSQL** lebih disarankan karena kuat untuk data relasional, indexing, JSON column, constraint, dan reporting.

MySQL tetap valid jika tim lebih familiar dengan ekosistem tersebut.

---

## 4. Prinsip Arsitektur

1. Gunakan modular monolith.
2. Pisahkan domain bisnis ke dalam folder `modules/`.
3. Jangan menumpuk semua logic di Livewire component.
4. Livewire hanya menangani UI state dan interaksi user.
5. Business logic utama diletakkan di `Actions` dan `Services`.
6. Gunakan `DTO` untuk data penting yang melewati banyak layer.
7. Gunakan `Enum` untuk status dan tipe domain.
8. Gunakan `Policy` dan `Permission` untuk keamanan.
9. Semua perubahan penting harus memiliki audit trail.
10. Payroll yang sudah final harus disimpan sebagai snapshot.
11. API dan Livewire harus menggunakan service layer yang sama.
12. Proses berat harus menggunakan queue.
13. Data sensitif harus dilindungi secara serius.
14. Semua table domain wajib tenant-aware jika HARIS berbentuk SaaS.
15. Testing payroll harus menjadi prioritas tinggi.

---

## 5. Struktur Root Project

```text
haris/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
├── modules/
├── public/
├── resources/
├── routes/
├── scripts/
├── storage/
├── tests/
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
├── vite.config.js
└── README.md
```

### Folder tambahan penting

| Folder                   | Fungsi                                                         |
| ------------------------ | -------------------------------------------------------------- |
| `modules/`               | Semua domain bisnis utama                                      |
| `docs/`                  | Dokumentasi arsitektur, flow bisnis, API, dan keputusan teknis |
| `scripts/`               | Script setup, maintenance, import/export, deployment helper    |
| `database/schema-notes/` | Catatan skema database dan keputusan relasi                    |
| `tests/Helpers/`         | Helper untuk testing payroll dan domain kompleks               |

---

## 6. Struktur Laravel

```text
app/
├── Actions/
├── Console/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Services/
└── Support/
```

### Fungsi folder

| Folder             | Fungsi                                            |
| ------------------ | ------------------------------------------------- |
| `Actions`          | Use case global atau lintas module                |
| `Http/Controllers` | API, webhook, auth, export, endpoint non-Livewire |
| `Http/Requests`    | Validasi request                                  |
| `Http/Resources`   | Response transformer untuk API                    |
| `Jobs`             | Proses asynchronous                               |
| `Events`           | Event domain                                      |
| `Listeners`        | Listener event                                    |
| `Notifications`    | Notifikasi email, database, broadcast             |
| `Policies`         | Otorisasi berbasis model                          |
| `Services`         | Service global lintas domain                      |
| `Support`          | Helper, trait, enum global, utility internal      |
| `Models`           | Model inti atau model global                      |

---

## 7. Struktur Domain Modular

HARIS menggunakan struktur domain modular.

```text
modules/
├── Auth/
├── Users/
├── RolesPermissions/
├── Organization/
├── MasterData/
├── Dashboard/
├── Attendance/
├── LeaveManagement/
├── Overtime/
├── Payroll/
├── Documents/
├── Notifications/
├── Reports/
├── AuditTrail/
├── Social/
├── Announcement/
├── Analytics/
├── Security/
├── Settings/
├── System/
└── Integrations/
```

### Struktur standar satu module

```text
modules/Example/
├── Actions/
├── DTOs/
├── Enums/
├── Events/
├── Jobs/
├── Listeners/
├── Livewire/
│   ├── Pages/
│   ├── Forms/
│   ├── Tables/
│   ├── Modals/
│   ├── Widgets/
│   └── Partials/
├── Models/
├── Notifications/
├── Policies/
├── Queries/
├── Repositories/
├── Requests/
├── Resources/
├── Rules/
├── Services/
├── Support/
├── Tests/
└── routes.php
```

### Penjelasan

| Folder         | Fungsi                                               |
| -------------- | ---------------------------------------------------- |
| `Actions`      | Aksi bisnis spesifik seperti create, update, approve |
| `DTOs`         | Object data terstruktur                              |
| `Enums`        | Status, type, dan konstanta domain                   |
| `Events`       | Domain event                                         |
| `Jobs`         | Background job domain                                |
| `Livewire`     | Komponen UI domain                                   |
| `Models`       | Model Eloquent domain                                |
| `Policies`     | Aturan akses domain                                  |
| `Queries`      | Query read-model kompleks                            |
| `Repositories` | Abstraksi query bila dibutuhkan                      |
| `Requests`     | Form request untuk API/controller                    |
| `Resources`    | API resource                                         |
| `Rules`        | Custom validation rule                               |
| `Services`     | Logika reusable domain                               |
| `Support`      | Helper internal module                               |
| `Tests`        | Test khusus module                                   |
| `routes.php`   | Route module                                         |

---

## 8. Struktur Livewire

Livewire dipisah berdasarkan domain dan jenis halaman.

```text
modules/
├── Dashboard/
│   └── Livewire/
│       ├── Pages/
│       ├── Widgets/
│       └── Tables/
├── Users/
│   └── Livewire/
│       ├── Pages/
│       ├── Forms/
│       ├── Tables/
│       └── Modals/
├── Payroll/
│   └── Livewire/
│       ├── Pages/
│       ├── Forms/
│       ├── Tables/
│       ├── Modals/
│       ├── Widgets/
│       └── Partials/
```

### Contoh module payroll

```text
modules/Payroll/Livewire/
├── Pages/
│   ├── PayrollPeriodIndexPage.php
│   ├── PayrollPeriodDetailPage.php
│   ├── PayrollRunPage.php
│   ├── PayrollApprovalPage.php
│   └── PayslipDetailPage.php
├── Forms/
│   ├── PayrollComponentForm.php
│   ├── PayrollProfileForm.php
│   └── ManualAdjustmentForm.php
├── Tables/
│   ├── PayrollPeriodTable.php
│   ├── PayrollRunTable.php
│   └── PayslipTable.php
├── Modals/
│   ├── ApprovePayrollModal.php
│   ├── RejectPayrollModal.php
│   └── RecalculatePayrollModal.php
├── Widgets/
│   ├── PayrollSummaryWidget.php
│   └── PayrollCostWidget.php
└── Partials/
    ├── PayrollStatusBadge.php
    └── PayrollTimelinePanel.php
```

### Blade views

```text
resources/views/
├── components/
├── layouts/
├── partials/
├── livewire/
└── modules/
    ├── dashboard/
    ├── users/
    ├── attendance/
    ├── payroll/
    ├── reports/
    └── settings/
```

---

## 9. Modul Bisnis HARIS

### 9.1 Auth

Fungsi:

- login
- logout
- register company owner
- reset password
- session management
- optional SSO
- multi-device/session control

### 9.2 Users

Fungsi:

- manajemen pengguna
- profil pengguna
- status aktif/nonaktif
- relasi user ke company
- relasi user ke employee
- relasi user ke role

### 9.3 RolesPermissions

Fungsi:

- role management
- permission granular
- hak akses menu
- hak akses aksi per modul
- permission berbasis company

### 9.4 Organization

Fungsi:

- struktur perusahaan
- cabang
- departemen
- jabatan
- hirarki approval
- mapping atasan dan bawahan

### 9.5 MasterData

Fungsi:

- kategori global
- status global
- lookup values
- parameter sistem
- konfigurasi dropdown

### 9.6 Dashboard

Fungsi:

- ringkasan statistik HR
- statistik payroll
- widget absensi
- approval pending
- pengumuman terbaru
- alert payroll

### 9.7 Attendance

Fungsi:

- presensi check-in/check-out
- GPS location
- selfie attendance
- geofencing
- multi-location attendance
- koreksi absensi
- rekap absensi

### 9.8 LeaveManagement

Fungsi:

- cuti tahunan
- izin
- sakit
- unpaid leave
- approval cuti
- custom leave policy
- saldo cuti

### 9.9 Overtime

Fungsi:

- request lembur
- approval lembur
- perhitungan lembur
- integrasi ke payroll

### 9.10 Payroll

Fungsi:

- profil payroll karyawan
- payroll group
- payroll period
- payroll run
- payroll component
- BPJS
- PPh 21
- THR
- komisi
- potongan
- kasbon
- payslip

### 9.11 Documents

Fungsi:

- upload file
- dokumen karyawan
- dokumen payroll
- versioning dokumen
- akses file berbasis permission

### 9.12 Notifications

Fungsi:

- database notification
- email notification
- broadcast notification
- reminder sistem
- notification template

### 9.13 Reports

Fungsi:

- laporan HR
- laporan attendance
- laporan payroll
- export Excel/PDF
- report besar via queue

### 9.14 AuditTrail

Fungsi:

- log aktivitas user
- log perubahan data
- log approval
- log payroll
- log security

### 9.15 Social

Fungsi:

- social feed internal
- posting
- komentar
- like/reaction
- moderation

### 9.16 Announcement

Fungsi:

- pengumuman perusahaan
- pengumuman cabang
- scheduled announcement
- read receipt

### 9.17 Analytics

Fungsi:

- statistik absensi
- statistik produktivitas
- tren payroll cost
- statistik turnover
- analytics karyawan

### 9.18 Security

Fungsi:

- abuse detection
- device control
- suspicious login detection
- access restriction by role, location, device

### 9.19 Settings

Fungsi:

- company settings
- payroll settings
- attendance settings
- notification settings
- branding dasar

### 9.20 System

Fungsi:

- system versioning
- maintenance mode
- auto update mechanism
- system health check

### 9.21 Integrations

Fungsi:

- API eksternal
- webhook
- payment gateway
- bank disbursement
- WhatsApp API
- import/export antar sistem

---

## 10. Fitur Utama Produk

### 10.1 MVP

- Register dan login
- Multi-company basic
- User management
- Role permission basic
- Employee management
- Branch dan department basic
- Attendance GPS + selfie
- Shift kerja
- Cuti dan izin
- Lembur
- Kasbon
- Payroll sederhana
- Generate payslip PDF
- Dashboard owner/admin
- Audit log dasar

### 10.2 Version 1

- Payroll group
- Payroll component
- BPJS basic
- PPh 21 basic
- THR basic
- Export Excel
- Approval flow
- Notification
- Subscription billing
- Employee self service

### 10.3 Version 2

- Multi-branch advanced
- Mobile API
- Advanced payslip
- Advanced reporting
- Bank disbursement
- OpenAPI documentation
- Webhook integration
- Import Excel dengan rollback

### 10.4 Version 3

- Advanced tax engine
- Advanced BPJS engine
- Social internal platform
- Analytics dashboard
- Abuse detection
- Enterprise workflow
- Custom approval builder
- Integration marketplace

---

## 11. Multi-Tenant SaaS Architecture

HARIS harus mendukung banyak perusahaan dalam satu sistem.

### Prinsip multi-tenancy

1. Semua data bisnis harus terhubung ke `company_id`.
2. Query harus selalu dibatasi berdasarkan company aktif.
3. User bisa tergabung ke lebih dari satu company jika dibutuhkan.
4. Role dan permission bisa berbeda per company.
5. Data antar company tidak boleh bocor.
6. File storage harus dipisahkan berdasarkan company.
7. Audit log harus menyimpan company context.

### Tabel penting

```text
companies
company_users
company_settings
branches
departments
positions
employees
```

### Rekomendasi implementasi

- Middleware `SetCurrentCompany`
- Global scope untuk model tenant-aware
- Trait `BelongsToCompany`
- Policy untuk validasi akses per record
- Index pada `company_id`
- Audit log untuk perpindahan company aktif

### Contoh trait konseptual

```php
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (app()->bound('currentCompany')) {
                $model->company_id = app('currentCompany')->id;
            }
        });

        static::addGlobalScope('company', function ($query) {
            if (app()->bound('currentCompany')) {
                $query->where('company_id', app('currentCompany')->id);
            }
        });
    }
}
```

---

## 12. Role dan Permission

Gunakan kombinasi:

- role-based access control
- permission granular
- policy berbasis record
- company-scoped permission

### Role awal

```text
Super Admin
Company Owner
Admin HR
Payroll Admin
Manager
Approver
Employee
Viewer
```

### Contoh permission

```text
users.view
users.create
users.update
users.delete

employees.view
employees.create
employees.update
employees.delete

attendance.view
attendance.create
attendance.correct
attendance.approve_correction

leave.view
leave.create
leave.approve
leave.reject

overtime.view
overtime.create
overtime.approve
overtime.reject

payroll.view
payroll.process
payroll.approve
payroll.finalize
payroll.publish_payslip
payroll.mark_paid

reports.view
reports.export

settings.manage
audit.view
```

### Prinsip implementasi

- Gunakan package `spatie/laravel-permission`.
- Gunakan middleware permission untuk route.
- Gunakan policy untuk record-level authorization.
- Jangan hanya mengandalkan menu hiding.
- Semua action sensitif harus dicek ulang di backend.

---

## 13. Database Core Schema

### 13.1 Auth dan Access Control

```text
users
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
password_reset_tokens
sessions
personal_access_tokens
```

### 13.2 Company dan Organization

```text
companies
company_users
company_settings
branches
departments
positions
employee_structures
```

### 13.3 Employee

```text
employees
employee_profiles
employee_contacts
employee_documents
employee_emergency_contacts
```

### 13.4 Attendance dan Leave

```text
shifts
employee_shifts
attendances
attendance_corrections
leave_types
leave_policies
leave_balances
leave_requests
```

### 13.5 Payroll

```text
employee_payroll_profiles
payroll_groups
payroll_periods
payroll_runs
payroll_run_items
payroll_components
employee_payroll_components
payslips
payroll_disbursements
```

### 13.6 BPJS dan Tax

```text
bpjs_profiles
bpjs_rules
payroll_bpjs_results
tax_statuses
tax_rules
tax_rule_brackets
payroll_tax_results
employee_tax_histories
```

### 13.7 Loan, Overtime, Reports, Audit

```text
overtime_requests
overtime_results
employee_loans
employee_loan_installments
report_jobs
report_exports
report_snapshots
audit_logs
activity_logs
system_error_logs
```

---

## 14. Payroll, BPJS, Pajak, dan Payslip

Payroll adalah modul paling sensitif karena berkaitan langsung dengan gaji karyawan. Semua perhitungan harus transparan, bisa diaudit, dan memiliki snapshot final.

### 14.1 Prinsip utama desain payroll

1. Gunakan snapshot per periode payroll.
2. Pisahkan data master, transaksi, dan hasil payroll.
3. Simpan semua komponen rinci.
4. Dukung effective date untuk rule BPJS dan pajak.
5. Jangan hitung ulang payroll lama dari master data yang sudah berubah.
6. Payroll finalized harus immutable.
7. Koreksi payroll dilakukan lewat adjustment, bukan edit diam-diam.

---

### 14.2 employee_payroll_profiles

Menyimpan profil payroll per karyawan.

```text
employee_payroll_profiles
- id
- company_id
- employee_id
- employee_code
- tax_status_id
- bpjs_profile_id
- payroll_group_id
- bank_name
- bank_account_name
- bank_account_number
- npwp_number
- bpjs_kesehatan_number
- bpjs_tk_number
- basic_salary
- payment_type                    # monthly / daily / hourly
- join_date
- resign_date
- is_taxable
- is_bpjs_kesehatan_enrolled
- is_bpjs_tk_enrolled
- is_overtime_eligible
- created_at
- updated_at
```

---

### 14.3 payroll_groups

Kelompok payroll untuk pengaturan proses per lokasi/divisi/perusahaan.

```text
payroll_groups
- id
- company_id
- code
- name
- pay_frequency                   # monthly / biweekly / weekly
- payroll_day
- overtime_policy_id
- attendance_policy_id
- leave_policy_id
- created_at
- updated_at
```

---

### 14.4 payroll_periods

Periode payroll yang akan diproses.

```text
payroll_periods
- id
- company_id
- payroll_group_id
- period_name
- start_date
- end_date
- pay_date
- status                          # draft / processing / finalized / paid / cancelled
- closed_at
- created_by
- updated_by
- created_at
- updated_at
```

---

### 14.5 payroll_runs

Header proses payroll per karyawan per periode.

```text
payroll_runs
- id
- company_id
- payroll_period_id
- employee_id
- employee_payroll_profile_id
- payroll_number
- basic_salary_snapshot
- gross_salary
- total_allowance
- total_deduction
- total_bpjs_company
- total_bpjs_employee
- total_pph21
- total_overtime
- total_loan_deduction
- total_absence_deduction
- net_salary
- rounding_amount
- take_home_pay
- calculation_status              # draft / calculated / approved / paid
- calculated_at
- approved_at
- approved_by
- paid_at
- created_at
- updated_at
```

---

### 14.6 payroll_run_items

Rincian item payroll per payroll run.

```text
payroll_run_items
- id
- payroll_run_id
- component_code
- component_name
- component_type                  # earning / deduction / employer_cost / tax
- source_type                     # system / manual / overtime / attendance / loan / tax / bpjs
- reference_id
- is_taxable
- is_bpjs_applicable
- quantity
- rate
- amount
- notes
- sort_order
- created_at
- updated_at
```

Tabel ini adalah sumber utama untuk detail slip gaji.

---

### 14.7 payroll_components

Master semua komponen payroll.

```text
payroll_components
- id
- company_id
- code                            # BASIC, ALLOW_TRANSPORT, BPJS_JHT_EMP
- name
- category                        # earning / deduction / employer_cost / tax / reimbursement
- calculation_method              # fixed / formula / percentage / manual / imported
- default_taxable
- default_bpjs_applicable
- display_on_payslip
- affects_take_home_pay
- is_active
- created_at
- updated_at
```

---

### 14.8 employee_payroll_components

Komponen payroll per karyawan.

```text
employee_payroll_components
- id
- company_id
- employee_id
- payroll_component_id
- amount
- percentage_value
- effective_start_date
- effective_end_date
- is_active
- notes
- created_at
- updated_at
```

---

### 14.9 BPJS tables

#### bpjs_profiles

```text
bpjs_profiles
- id
- company_id
- employee_id
- bpjs_kesehatan_number
- bpjs_tk_number
- kelas_rawat
- base_salary_override
- is_bpjs_kesehatan_enrolled
- is_jht_enrolled
- is_jp_enrolled
- is_jkk_enrolled
- is_jkm_enrolled
- effective_start_date
- effective_end_date
- created_at
- updated_at
```

#### bpjs_rules

```text
bpjs_rules
- id
- company_id
- rule_name
- bpjs_type                       # kesehatan / jht / jp / jkk / jkm
- participant_portion_type        # employee / employer / both
- employee_rate
- employer_rate
- max_salary_base
- min_salary_base
- company_risk_level
- effective_start_date
- effective_end_date
- is_active
- created_at
- updated_at
```

#### payroll_bpjs_results

```text
payroll_bpjs_results
- id
- payroll_run_id
- bpjs_type
- salary_base
- employee_rate
- employer_rate
- employee_amount
- employer_amount
- rule_snapshot_json
- created_at
- updated_at
```

Gunakan `rule_snapshot_json` agar histori tetap aman walaupun aturan berubah.

---

### 14.10 Tax tables

#### tax_statuses

```text
tax_statuses
- id
- code                            # TK0, K0, K1, K2, K3
- name
- ptkp_amount_yearly
- description
- effective_start_date
- effective_end_date
- is_active
- created_at
- updated_at
```

#### tax_rules

```text
tax_rules
- id
- company_id
- rule_name
- tax_method                      # gross / gross_up / nett / ter
- tax_category                    # pph21
- effective_start_date
- effective_end_date
- is_active
- created_at
- updated_at
```

#### tax_rule_brackets

```text
tax_rule_brackets
- id
- tax_rule_id
- bracket_order
- min_amount
- max_amount
- tax_rate
- created_at
- updated_at
```

#### payroll_tax_results

```text
payroll_tax_results
- id
- payroll_run_id
- tax_rule_id
- tax_status_id
- taxable_income_monthly
- taxable_income_yearly_projection
- job_expense_amount
- pension_cost_amount
- net_income_yearly
- ptkp_amount_yearly
- pkp_amount_yearly
- yearly_tax_amount
- monthly_tax_amount
- method_snapshot_json
- created_at
- updated_at
```

#### employee_tax_histories

```text
employee_tax_histories
- id
- company_id
- employee_id
- tax_year
- total_gross_income
- total_taxable_income
- total_pph21_paid
- total_bpjs_employee
- total_bpjs_company
- total_other_deduction
- created_at
- updated_at
```

---

### 14.11 Payslip dan payment

#### payslips

```text
payslips
- id
- company_id
- payroll_run_id
- payslip_number
- employee_id
- payroll_period_id
- issue_date
- file_path
- file_disk
- is_published
- published_at
- viewed_at
- email_sent_at
- created_at
- updated_at
```

#### payroll_disbursements

```text
payroll_disbursements
- id
- company_id
- payroll_period_id
- employee_id
- payroll_run_id
- payment_method                   # bank_transfer / cash / virtual_account
- bank_name
- bank_account_number
- account_name
- transfer_reference_number
- amount
- payment_status                   # pending / processing / success / failed
- paid_at
- failure_reason
- created_at
- updated_at
```

---

### 14.12 Alur perhitungan payroll

1. Buat payroll period.
2. Tentukan payroll group.
3. Lock date range.
4. Ambil salary profile.
5. Ambil payroll component aktif.
6. Ambil attendance summary.
7. Ambil overtime approved.
8. Ambil loan installment.
9. Hitung pendapatan.
10. Hitung potongan.
11. Hitung BPJS.
12. Hitung PPh 21.
13. Hitung take home pay.
14. Simpan payroll run.
15. Simpan payroll run items.
16. Simpan BPJS result.
17. Simpan tax result.
18. Generate payslip.
19. Review dan approve.
20. Finalize payroll.
21. Publish payslip.
22. Mark as paid.

---

## 15. Attendance, Leave, Overtime, dan Loan

### 15.1 Attendance

```text
attendances
- id
- company_id
- employee_id
- shift_id
- attendance_date
- check_in_time
- check_out_time
- check_in_latitude
- check_in_longitude
- check_out_latitude
- check_out_longitude
- check_in_photo_path
- check_out_photo_path
- gps_accuracy
- device_id
- ip_address
- user_agent
- is_mock_location_detected
- status
- late_minutes
- early_leave_minutes
- created_at
- updated_at
```

### 15.2 attendance_summaries

```text
attendance_summaries
- id
- company_id
- employee_id
- payroll_period_id
- total_work_days
- total_present_days
- total_absent_days
- total_late_count
- total_late_minutes
- total_early_leave_minutes
- total_paid_leave_days
- total_unpaid_leave_days
- total_sick_days
- total_permission_days
- deduction_amount
- summary_snapshot_json
- created_at
- updated_at
```

### 15.3 Leave requests

```text
leave_requests
- id
- company_id
- employee_id
- leave_type_id
- start_date
- end_date
- total_days
- reason
- attachment_path
- status                         # draft / submitted / approved / rejected / cancelled
- approved_by
- approved_at
- rejected_by
- rejected_at
- rejection_reason
- created_at
- updated_at
```

### 15.4 Overtime

#### overtime_requests

```text
overtime_requests
- id
- company_id
- employee_id
- attendance_date
- start_time
- end_time
- total_hours
- overtime_type
- multiplier_snapshot
- status                         # draft / submitted / approved / rejected / paid
- approved_by
- approved_at
- notes
- created_at
- updated_at
```

#### overtime_results

```text
overtime_results
- id
- payroll_run_id
- overtime_request_id
- hourly_rate_snapshot
- total_hours
- multiplier_total
- amount
- created_at
- updated_at
```

### 15.5 Loan / Kasbon

#### employee_loans

```text
employee_loans
- id
- company_id
- employee_id
- loan_number
- loan_type                       # kasbon / pinjaman
- principal_amount
- installment_amount
- total_installments
- paid_installments
- remaining_amount
- start_date
- end_date
- status                          # active / closed / defaulted / cancelled
- notes
- created_at
- updated_at
```

#### employee_loan_installments

```text
employee_loan_installments
- id
- company_id
- employee_loan_id
- payroll_period_id
- installment_number
- amount
- due_date
- paid_at
- payroll_run_id
- status                          # pending / deducted / paid / skipped
- created_at
- updated_at
```

---

## 16. API Design

HARIS harus API-ready untuk mobile app, integrasi eksternal, dan employee self service.

### Prinsip API

- Gunakan `/api/v1`
- Gunakan Laravel Sanctum
- Gunakan API Resource
- Gunakan Form Request validation
- Gunakan pagination standard
- Gunakan rate limiting
- Gunakan idempotency key untuk proses sensitif
- Gunakan OpenAPI/Swagger
- Gunakan webhook signature untuk integrasi eksternal

### Struktur endpoint

```text
/api/v1/
├── /auth
├── /companies
├── /users
├── /employees
├── /attendance
├── /leave-requests
├── /overtimes
├── /loans
├── /payroll-groups
├── /payroll-periods
├── /payroll-runs
├── /payroll-components
├── /bpjs
├── /tax
├── /payslips
├── /reports
└── /webhooks
```

### Auth API

```text
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me
```

### Payroll Period API

```text
GET    /api/v1/payroll-periods
POST   /api/v1/payroll-periods
GET    /api/v1/payroll-periods/{id}
PUT    /api/v1/payroll-periods/{id}
POST   /api/v1/payroll-periods/{id}/process
POST   /api/v1/payroll-periods/{id}/finalize
POST   /api/v1/payroll-periods/{id}/publish-payslips
```

### Payroll Run API

```text
GET    /api/v1/payroll-runs
GET    /api/v1/payroll-runs/{id}
GET    /api/v1/payroll-runs/{id}/items
GET    /api/v1/payroll-runs/{id}/bpjs
GET    /api/v1/payroll-runs/{id}/tax
POST   /api/v1/payroll-runs/{id}/approve
POST   /api/v1/payroll-runs/{id}/mark-paid
```

### BPJS API

```text
GET    /api/v1/bpjs/rules
POST   /api/v1/bpjs/rules
PUT    /api/v1/bpjs/rules/{id}
GET    /api/v1/employees/{employee}/bpjs-profile
PUT    /api/v1/employees/{employee}/bpjs-profile
GET    /api/v1/payroll-runs/{id}/bpjs-results
```

### Tax API

```text
GET    /api/v1/tax/statuses
POST   /api/v1/tax/statuses
GET    /api/v1/tax/rules
POST   /api/v1/tax/rules
PUT    /api/v1/tax/rules/{id}
GET    /api/v1/payroll-runs/{id}/tax-results
GET    /api/v1/employees/{employee}/tax-history
```

### Payslip API

```text
GET    /api/v1/payslips
GET    /api/v1/payslips/{id}
GET    /api/v1/payslips/{id}/download
POST   /api/v1/payslips/{id}/send-email
POST   /api/v1/payslips/{id}/publish
```

### Standard response

#### Success

```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": {}
}
```

#### Error

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": ["The field is required."]
    }
}
```

#### Pagination

```json
{
    "success": true,
    "message": "List retrieved successfully",
    "data": [],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "total": 200
    }
}
```

---

## 17. Service Layer dan Action Pattern

Agar aplikasi tidak menjadi fat Livewire component, gunakan pola berikut:

```text
Livewire Component
        |
        v
Action Class
        |
        v
Service / Domain Logic
        |
        v
Model / Query / Repository
```

### Contoh Payroll module

```text
modules/Payroll/
├── Actions/
│   ├── CreatePayrollPeriod.php
│   ├── ProcessPayrollPeriod.php
│   ├── CalculatePayrollRun.php
│   ├── ApprovePayrollRun.php
│   ├── FinalizePayrollPeriod.php
│   ├── PublishPayslips.php
│   └── MarkPayrollAsPaid.php
├── Services/
│   ├── PayrollCalculationService.php
│   ├── BPJSCalculationService.php
│   ├── TaxCalculationService.php
│   ├── PayslipGenerationService.php
│   └── PayrollAuditService.php
```

### Prinsip

- Livewire menangani UI.
- Controller menangani HTTP/API.
- Action menangani satu use case.
- Service menangani logic reusable.
- Query class menangani query kompleks.
- Model menangani relasi dan behavior sederhana.

---

## 18. Workflow dan Approval

HARIS membutuhkan approval flow untuk:

- cuti
- izin
- lembur
- koreksi absensi
- payroll
- kasbon
- dokumen
- perubahan gaji

### Tabel workflow

```text
approval_flows
approval_steps
approval_logs
task_assignments
workflow_rules
```

### Status umum workflow

```text
draft
submitted
in_review
approved
rejected
cancelled
returned
```

### Prinsip approval

1. Approval harus memiliki audit log.
2. Approval harus menyimpan siapa, kapan, dan catatan.
3. Approval bisa berbasis role, jabatan, cabang, atau atasan langsung.
4. Approval payroll harus lebih ketat dibanding approval biasa.
5. Data yang sudah approved tidak boleh berubah tanpa mekanisme koreksi.

---

## 19. Audit Trail

Audit trail wajib untuk aplikasi HRIS dan payroll.

### Yang dicatat

- login dan logout penting
- perubahan data karyawan
- perubahan gaji
- perubahan tax status
- perubahan BPJS profile
- perubahan payroll component
- proses payroll
- approval payroll
- publish payslip
- export data
- perubahan permission
- penghapusan data
- akses data sensitif

### Struktur audit_logs

```text
audit_logs
- id
- company_id
- user_id
- action
- module
- entity_type
- entity_id
- old_values_json
- new_values_json
- ip_address
- user_agent
- created_at
```

### Prinsip

- Audit log tidak boleh mudah dihapus.
- Audit log harus bisa difilter berdasarkan user, module, entity, dan tanggal.
- Untuk data sensitif, hindari menyimpan plain text berlebihan di audit log.

---

## 20. Notification System

### Channel

- database notification
- email
- broadcast
- optional WhatsApp
- optional push notification

### Event penting

- cuti diajukan
- cuti disetujui/ditolak
- lembur diajukan
- payroll siap direview
- payroll disetujui
- payslip diterbitkan
- kasbon disetujui
- report selesai dibuat
- login mencurigakan
- perubahan data salary

### Struktur module

```text
modules/Notifications/
├── Actions/
├── Events/
├── Listeners/
├── Models/
├── Notifications/
├── Services/
└── Templates/
```

---

## 21. Reporting dan Export

Reporting jangan dicampur ke controller biasa.

```text
modules/Reports/
├── Actions/
├── DTOs/
├── Exports/
├── Jobs/
├── Livewire/
├── Models/
├── Queries/
└── Services/
```

### Jenis report

- laporan karyawan
- laporan absensi
- laporan keterlambatan
- laporan cuti
- laporan lembur
- laporan payroll
- laporan BPJS
- laporan pajak
- laporan kasbon
- laporan payroll cost
- laporan audit

### Prinsip report besar

- Jalankan via queue.
- Simpan hasil export.
- Kirim notifikasi saat selesai.
- Berikan expiry untuk file export.
- Catat audit log setiap export data sensitif.

---

## 22. Security dan Data Protection

HARIS menyimpan data sensitif, seperti:

- gaji
- slip gaji
- nomor rekening
- NPWP
- nomor BPJS
- foto selfie absensi
- lokasi GPS
- dokumen pribadi karyawan
- riwayat pajak
- histori payroll

### Wajib diterapkan

- HTTPS
- password hashing
- role-based access control
- policy per model
- company isolation
- signed URL untuk file sensitif
- rate limiting
- session timeout
- two-factor authentication untuk admin penting
- audit trail
- backup terenkripsi
- proteksi export data
- validasi input
- CSRF protection untuk web
- CORS policy untuk API
- secure cookie
- environment secret management

### Field yang layak dienkripsi

```text
npwp_number
bank_account_number
bpjs_kesehatan_number
bpjs_tk_number
tax_snapshot_json
salary_snapshot
```

### Proteksi file

- File slip gaji tidak boleh public.
- Gunakan signed temporary URL.
- Pisahkan folder storage per company.
- Catat akses file sensitif.
- Expire link download.

---

## 23. Payroll Edge Cases

Payroll harus menangani kasus khusus agar hasil tidak salah.

### Kasus wajib didukung

- karyawan masuk di tengah bulan
- karyawan resign di tengah bulan
- unpaid leave
- paid leave
- sakit dibayar
- sakit tidak dibayar
- absensi tidak lengkap
- lupa check-out
- lembur melewati tengah malam
- shift malam
- perubahan gaji di tengah periode
- rapel gaji
- bonus tahunan
- THR
- komisi
- reimbursement
- koreksi payroll bulan sebelumnya
- payroll reversal
- payroll recalculation
- payroll lock
- payroll unlock terbatas
- rounding
- negative net salary
- kasbon melebihi gaji
- payroll untuk karyawan harian
- payroll untuk part-time
- payroll untuk hourly worker

### Prinsip koreksi

Payroll yang sudah finalized tidak boleh diubah langsung.

Gunakan pendekatan:

```text
Finalized payroll salah
        |
        v
Buat adjustment
        |
        v
Adjustment masuk payroll periode berikutnya
        |
        v
Audit log tetap aman
```

---

## 24. Attendance Edge Cases

Attendance harus menangani kondisi lapangan.

### Kasus penting

- GPS tidak akurat
- karyawan absen di luar radius
- device berbeda
- koneksi internet buruk
- shift melewati tengah malam
- hari libur nasional
- jadwal kerja fleksibel
- cabang lebih dari satu
- karyawan pindah lokasi kerja
- koreksi absensi manual
- approval koreksi absensi
- potensi fake GPS
- lupa check-in
- lupa check-out
- absen dobel
- absen di tanggal salah
- mobile browser permission ditolak

### Data tambahan

```text
device_id
ip_address
user_agent
gps_accuracy
is_mock_location_detected
attendance_source
correction_reason
corrected_by
corrected_at
```

### Prinsip

- Admin boleh koreksi, tetapi semua koreksi harus tercatat.
- Foto selfie dan GPS hanya valid jika timestamp jelas.
- Sistem harus menyimpan raw attendance sebelum koreksi.
- Koreksi attendance harus berdampak ke attendance summary dan payroll.

---

## 25. Testing Strategy

Testing harus diprioritaskan untuk domain sensitif.

### Struktur test

```text
tests/
├── Feature/
├── Unit/
├── Browser/
└── Helpers/
```

### Test prioritas tinggi

1. Login dan authorization.
2. Company isolation.
3. Role permission.
4. Employee CRUD.
5. Attendance check-in/check-out.
6. Leave approval.
7. Overtime approval.
8. Payroll calculation.
9. BPJS calculation.
10. PPh 21 calculation.
11. Payslip generation.
12. Payroll finalize.
13. Payroll lock.
14. Audit logging.
15. Export report.

### Payroll test wajib

- basic salary calculation
- allowance calculation
- deduction calculation
- overtime calculation
- loan deduction
- BPJS calculation
- tax calculation
- unpaid leave deduction
- mid-month join
- mid-month resign
- salary change
- payroll recalculation
- payroll lock
- payslip generation

### Prinsip

Setiap bug payroll di production wajib diubah menjadi automated test agar tidak terulang.

---

## 26. Queue, Scheduler, dan Background Jobs

HARIS membutuhkan background process untuk tugas berat.

### Gunakan queue untuk

- generate payslip massal
- kirim email payslip
- export report besar
- import data besar
- kirim reminder approval
- sinkronisasi data eksternal
- webhook delivery
- kalkulasi analytics

### Gunakan scheduler untuk

- reminder approval setiap pagi
- cleanup export lama setiap malam
- backup trigger jika diperlukan
- sinkronisasi master data
- payroll reminder
- check suspicious activity

### Struktur

```text
app/
├── Jobs/
└── Console/
    └── Commands/
```

Atau per module:

```text
modules/Payroll/
├── Jobs/
└── Console/
```

---

## 27. Observability dan Monitoring

Sistem harus mudah dipantau agar error payroll, queue, dan report cepat diketahui.

### Yang perlu dimonitor

- application error
- queue failed jobs
- job payroll gagal
- job generate payslip gagal
- email gagal
- API latency
- database slow query
- storage usage
- login mencurigakan
- export data besar
- perubahan salary massal
- penggunaan CPU/RAM/disk
- downtime

### Tools

- Laravel Log
- Laravel Horizon
- Sentry
- Bugsnag
- Uptime monitoring
- Database slow query log
- Server metrics dashboard

### Health check endpoint

```text
GET /health
GET /health/database
GET /health/queue
GET /health/storage
```

---

## 28. Backup, Restore, dan Disaster Recovery

Payroll dan data karyawan tidak boleh hilang.

### Backup minimal

- backup database harian
- backup file storage
- backup konfigurasi penting
- backup sebelum deployment besar
- backup sebelum migration besar
- retensi backup 30–90 hari

### Restore test

Backup tidak berguna jika tidak pernah diuji. Minimal lakukan restore test berkala di staging.

### Target awal

```text
RPO: maksimal kehilangan data 24 jam
RTO: sistem bisa dipulihkan dalam beberapa jam
```

### Data yang wajib ikut backup

- database
- file payslip
- dokumen karyawan
- foto attendance
- report snapshot
- configuration file penting

---

## 29. CI/CD dan Deployment

HARIS harus memiliki alur deployment aman.

### Environment

```text
local
development
staging
production
```

### Pipeline minimal

1. install dependency
2. run lint
3. run static analysis jika ada
4. run test
5. build frontend assets
6. run migration check
7. deploy to staging
8. smoke test
9. manual approval
10. deploy to production

### Deployment checklist

- backup database sebelum migration besar
- jalankan migration secara aman
- clear cache
- cache config
- cache route bila aman
- restart queue worker
- restart scheduler bila perlu
- cek health endpoint
- cek error log
- cek failed jobs

### Command Laravel umum

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan horizon:terminate
```

---

## 30. Import, Export, dan Onboarding Data

Saat onboarding customer baru, HARIS harus bisa menerima data dari Excel.

### Import awal

- data karyawan
- struktur organisasi
- jabatan
- cabang
- gaji pokok
- payroll component
- saldo kasbon
- saldo cuti
- data BPJS
- data pajak

### Fitur import penting

- template Excel
- preview sebelum import
- validasi data
- error report
- rollback import
- import history
- mapping kolom
- dry run import

### Export penting

- karyawan
- absensi
- cuti
- lembur
- payroll
- slip gaji
- BPJS
- pajak
- audit log

### Prinsip

Export data sensitif harus dicatat di audit trail.

---

## 31. Data Retention dan Arsip

Tidak semua data harus disimpan aktif selamanya, tetapi data payroll final harus aman untuk kebutuhan audit.

### Kebijakan data

- payroll finalized disimpan permanen sesuai kebutuhan audit
- payslip disimpan sesuai kebijakan perusahaan
- audit trail disimpan jangka panjang
- log teknis bisa dihapus setelah periode tertentu
- export sementara otomatis dihapus
- file temporary dibersihkan scheduler

### Data yang tidak boleh sembarang dihapus

- payroll finalized
- payroll run items
- payroll tax results
- payroll BPJS results
- payslip final
- audit trail
- approval log
- disbursement record

### Soft delete

Gunakan soft delete untuk data penting seperti:

- employee
- payroll profile
- payroll component
- document
- branch
- department

---

## 32. Product Roadmap

### Phase 0 — Validasi Ide

Tujuan:

- validasi target market
- validasi pain point
- validasi willingness to pay

Aktivitas:

- wawancara 20–30 bisnis
- buat landing page
- buat prototype Figma
- kumpulkan 5–10 beta user

Output:

- fitur MVP final
- target niche pertama
- harga awal
- daftar beta user

---

### Phase 1 — Foundation

Fitur:

- setup Laravel + Livewire
- auth
- company
- user
- employee
- branch
- department
- role permission basic
- dashboard dasar

Output:

- owner bisa daftar
- owner bisa buat company
- owner bisa tambah karyawan
- admin bisa login

---

### Phase 2 — Attendance MVP

Fitur:

- check-in
- check-out
- GPS
- selfie
- shift
- toleransi telat
- rekap absensi

Output:

- karyawan bisa absen dari HP
- admin bisa melihat kehadiran

---

### Phase 3 — Leave, Overtime, Loan

Fitur:

- request cuti
- approval cuti
- request lembur
- approval lembur
- kasbon
- rekap bulanan

Output:

- data transaksi HR siap masuk payroll

---

### Phase 4 — Payroll MVP

Fitur:

- payroll period
- payroll group basic
- payroll calculation
- bonus
- potongan
- lembur
- kasbon
- attendance deduction
- payslip PDF

Output:

- admin bisa hitung gaji
- owner bisa approve payroll
- karyawan bisa menerima payslip

---

### Phase 5 — Beta Testing

Target:

- 10–15 bisnis aktif
- 100–300 karyawan aktif
- minimal 1 siklus payroll per bisnis

Aktivitas:

- onboarding manual
- perbaikan bug
- perbaikan UI
- monitoring payroll real
- kumpulkan testimoni
- validasi harga

---

### Phase 6 — Launch Berbayar

Fitur tambahan:

- subscription plan
- payment gateway
- pricing page
- onboarding tutorial
- help center
- invoice subscription

Paket harga awal:

| Paket    |           Harga | Limit        |
| -------- | --------------: | ------------ |
| Starter  |  Rp49.000/bulan | 10 karyawan  |
| Growth   | Rp149.000/bulan | 30 karyawan  |
| Business | Rp299.000/bulan | 75 karyawan  |
| Custom   |   Hubungi sales | >75 karyawan |

---

### Phase 7 — Compliance dan Advanced Payroll

Fitur:

- BPJS basic
- PPh 21 basic
- THR
- payroll adjustment
- advanced payslip
- tax report
- BPJS report

---

### Phase 8 — Enterprise Features

Fitur:

- custom approval builder
- advanced analytics
- social internal
- announcement
- abuse detection
- bank disbursement
- API marketplace
- integration center

---

## 33. Milestone Development

```text
Minggu 1–2    : Validasi ide, interview, landing page
Minggu 3–4    : Prototype Figma, setup Laravel + Livewire
Minggu 5–8    : Auth, company, users, employee, role
Minggu 9–11   : Attendance GPS + selfie
Minggu 12–13  : Shift dan jadwal kerja
Minggu 14–16  : Cuti, izin, lembur, kasbon
Minggu 17–21  : Payroll MVP
Minggu 22–23  : Payslip PDF
Minggu 24–27  : Beta testing
Minggu 28–30  : Payment, pricing, launch
Minggu 31+    : BPJS, pajak, THR, advanced reporting
```

---

## 34. Struktur Dokumentasi

Untuk proyek nyata, jangan simpan semua detail di README. README harus menjadi pintu masuk utama, sedangkan detail ditempatkan di folder `docs/`.

```text
docs/
├── architecture/
│   ├── modular-monolith.md
│   ├── livewire-structure.md
│   ├── tenancy.md
│   └── service-layer.md
├── modules/
│   ├── payroll.md
│   ├── attendance.md
│   ├── leave.md
│   ├── overtime.md
│   ├── loan.md
│   ├── bpjs.md
│   └── tax.md
├── database/
│   ├── erd.md
│   ├── payroll-schema.md
│   └── audit-schema.md
├── api/
│   ├── v1-endpoints.md
│   ├── response-standard.md
│   └── openapi.yaml
├── security/
│   ├── access-control.md
│   ├── data-protection.md
│   └── audit-trail.md
├── deployment/
│   ├── ci-cd.md
│   ├── backup-restore.md
│   └── monitoring.md
└── adr/
    ├── 0001-use-modular-monolith.md
    ├── 0002-use-livewire.md
    └── 0003-use-postgresql.md
```

---

## 35. Konvensi Penamaan

### Livewire component

```text
IndexPage
CreatePage
EditPage
DetailPage
ApprovalPage
Form
Table
Modal
Widget
Panel
```

### Action

```text
CreateEmployee
UpdateEmployee
SubmitLeaveRequest
ApproveLeaveRequest
ProcessPayrollPeriod
GeneratePayslip
```

### Service

```text
PayrollCalculationService
BPJSCalculationService
TaxCalculationService
AttendanceSummaryService
AuditTrailService
NotificationService
```

### Enum

```text
EmployeeStatus
AttendanceStatus
LeaveRequestStatus
PayrollPeriodStatus
PayrollRunStatus
ApprovalStatus
PaymentStatus
```

### Permission

Format:

```text
module.action
```

Contoh:

```text
payroll.view
payroll.process
payroll.approve
attendance.correct
reports.export
```

---

## 36. Langkah Awal Development

Urutan paling aman untuk memulai HARIS:

1. Buat repository.
2. Install Laravel.
3. Install Livewire Starter Kit.
4. Setup Tailwind dan layout dashboard.
5. Setup database.
6. Setup auth.
7. Setup company dan multi-tenancy basic.
8. Setup role-permission.
9. Buat module Users.
10. Buat module Organization.
11. Buat module Employees.
12. Buat module Attendance.
13. Buat module Leave.
14. Buat module Overtime.
15. Buat module Loan.
16. Buat module Payroll.
17. Buat payslip PDF.
18. Tambahkan audit log.
19. Tambahkan notification.
20. Jalankan beta testing.

### Jangan mulai dari fitur kompleks

Untuk MVP, jangan langsung membangun:

- tax engine lengkap
- BPJS lengkap
- custom workflow builder
- social internal
- analytics kompleks
- mobile app native
- microservices

Mulai dari:

- employee
- attendance
- leave
- overtime
- loan
- payroll sederhana
- payslip

---

## 37. Kesimpulan

HARIS sebaiknya dibangun sebagai **modular monolith berbasis Laravel + Livewire**.

Pendekatan ini memberi keseimbangan antara:

- kecepatan development
- struktur yang rapi
- skalabilitas bisnis
- kemudahan maintenance
- kesiapan SaaS multi-company
- kesiapan audit dan compliance
- kesiapan API dan mobile
- kesiapan payroll kompleks

Blueprint ini sudah cukup kuat untuk memulai development, tetapi implementasinya harus tetap bertahap.

Prioritas awal HARIS:

1. Validasi user.
2. Bangun fondasi multi-tenant.
3. Bangun employee management.
4. Bangun attendance.
5. Bangun leave, overtime, dan loan.
6. Bangun payroll sederhana.
7. Bangun payslip.
8. Beta test dengan bisnis nyata.
9. Tambahkan BPJS, pajak, dan fitur compliance setelah payroll dasar stabil.

> Fokus awal bukan membuat HRIS paling lengkap, tetapi membuat sistem HR dan payroll yang benar-benar menyelesaikan masalah harian bisnis kecil: absensi, cuti, lembur, kasbon, hitung gaji, dan slip gaji.
