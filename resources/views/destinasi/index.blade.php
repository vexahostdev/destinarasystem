@extends('layouts.public')

@section('title', 'Direktori Destinasi Tapak Terkurasi — Destinara')
@section('meta_description', 'Inventaris dan direktori desa adat serta tapak pembelajaran terkurasi di seluruh Nusantara untuk ekskursi sekolah, penelitian, dan studi lapang.')

@section('content')
<main class="w-full bg-[#FFFFFF] pt-20 sm:pt-24 pb-20">
    <div class="flex flex-col w-full">
        <livewire:public.destination-catalog />
    </div>
</main>
@endsection
