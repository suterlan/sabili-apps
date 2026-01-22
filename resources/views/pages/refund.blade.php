@extends('layouts.guest')

@section('title', 'Kebijakan Pengembalian Dana')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Kebijakan Pengembalian Dana</h1>
            <p class="mt-2 text-lg text-gray-500">Transparansi layanan untuk kepercayaan Anda.</p>
        </div>

        <div
            class="prose prose-amber max-w-none text-gray-600 space-y-6 bg-white p-8 rounded-xl shadow-sm border border-gray-100">
            <p>Kami berkomitmen memberikan layanan terbaik. Namun, sebagai penyedia jasa, kami memiliki kebijakan
                pengembalian dana sebagai berikut:</p>

            <section>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500"></i> 1. Kondisi Refund yang Diterima
                </h3>
                <div class="ml-7">
                    <p class="mb-2">Pengembalian dana dapat diajukan apabila:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Terjadi pembayaran ganda (double payment) akibat kesalahan sistem.</li>
                        <li>Layanan belum diproses sama sekali oleh tim Pendamping Sabili dalam waktu 7x24 jam setelah
                            pembayaran dikonfirmasi.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-times-circle text-red-500"></i> 2. Kondisi Non-Refundable
                </h3>
                <div class="ml-7">
                    <p class="bg-red-50 p-4 rounded-md text-red-800 text-sm mb-3 border-l-4 border-red-500">
                        <strong>PENTING:</strong> Dana <strong>TIDAK DAPAT</strong> dikembalikan apabila proses pendampingan
                        sudah berjalan.
                    </p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Data sudah diinput ke sistem SiHalal/OSS.</li>
                        <li>Pengajuan ditolak oleh Komite Fatwa karena bahan baku tidak memenuhi standar syariat.</li>
                        <li>Pemohon membatalkan pengajuan sepihak saat proses berjalan.</li>
                    </ul>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-bold text-gray-900">3. Cara Mengajukan</h3>
                <p>Silakan hubungi kami melalui WhatsApp <strong>0857-1295-3879</strong> atau email ke
                    <strong>sabiliapps@gmail.com</strong> dengan menyertakan bukti transfer.</p>
            </section>
        </div>
    </div>
@endsection
