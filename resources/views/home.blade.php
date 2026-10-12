{{-- 
    Destinara System — Beranda dialihkan ke Katalog Destinasi.
    Landing page profil perusahaan berpusat di Destinara Compro.
--}}
@php
    header('Location: ' . route('destinasi.index'));
    exit;
@endphp
