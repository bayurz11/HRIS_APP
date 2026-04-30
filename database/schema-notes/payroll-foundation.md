# Payroll Foundation Schema Notes

Schema awal menargetkan kebutuhan payroll enterprise secara bertahap:

- Profil payroll karyawan dipisahkan dari data karyawan umum.
- Komponen payroll employee support fixed amount dan percentage.
- Payroll run menyimpan total snapshot untuk auditing dan payslip.
- Detail per komponen disimpan di `payroll_run_items`.
- BPJS dan tax result dipisah agar perhitungan dan audit lebih eksplisit.
