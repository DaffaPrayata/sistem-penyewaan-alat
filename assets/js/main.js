// assets/js/main.js

// Validasi ringan di form transaksi sewa: pastikan minimal satu alat dicentang.
// (Validasi utama & aman tetap dilakukan di server pada proses_sewa.php)
document.addEventListener('DOMContentLoaded', function () {
    var formSewa = document.getElementById('formSewa');
    if (!formSewa) return;

    formSewa.addEventListener('submit', function (e) {
        var dicentang = formSewa.querySelectorAll('.cek-alat:checked');
        if (dicentang.length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu alat yang akan disewa.');
        }
    });
});
