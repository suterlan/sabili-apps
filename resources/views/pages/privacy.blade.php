@extends('layouts.guest')

@section('title', 'Kebijakan Privasi')

@section('content')
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Kebijakan Privasi</h1>
                <p class="mt-4 text-lg text-gray-500">
                    Bagaimana Sabili mengumpulkan, menggunakan, dan melindungi data Anda.
                </p>
                <p class="mt-2 text-sm text-gray-400">Terakhir diperbarui: {{ date('d F Y') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 sm:p-10 space-y-8 text-gray-600 leading-relaxed">

                    <p>
                        PT/Komunitas Sabili ("Kami", "Sabili") menghormati privasi Anda dan berkomitmen untuk melindungi
                        data pribadi yang Anda bagikan kepada kami. Kebijakan Privasi ini menjelaskan bagaimana kami
                        mengelola informasi Anda saat Anda menggunakan layanan pendampingan usaha dan sertifikasi halal
                        kami.
                    </p>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                            Informasi yang Kami Kumpulkan
                        </h3>
                        <p class="mb-3">Untuk memproses layanan legalitas (NIB) dan Sertifikasi Halal, kami perlu
                            mengumpulkan data yang bersifat sensitif dan spesifik, meliputi:</p>
                        <ul class="list-disc pl-12 space-y-2">
                            <li><strong>Data Identitas:</strong> Nama Lengkap, Nomor Induk Kependudukan (NIK), Alamat sesuai
                                KTP, dan foto KTP (jika diperlukan untuk verifikasi SiHalal).</li>
                            <li><strong>Data Usaha:</strong> Nama Usaha, Alamat Lokasi Usaha, Nomor Induk Berusaha (NIB),
                                NPWP, dan Omzet Tahunan.</li>
                            <li><strong>Data Produksi:</strong> Komposisi bahan baku, diagram alur produksi, dan foto
                                produk.</li>
                            <li><strong>Data Kontak:</strong> Alamat email dan nomor WhatsApp aktif.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span>
                            Penggunaan Informasi
                        </h3>
                        <p class="mb-3">Kami menggunakan data tersebut semata-mata untuk:</p>
                        <ul class="list-disc pl-12 space-y-2">
                            <li>Menginput data pengajuan sertifikasi halal ke sistem pemerintah (SiHalal / BPJPH).</li>
                            <li>Memverifikasi kelayakan usaha Anda sesuai standar regulasi (Self Declare atau Reguler).</li>
                            <li>Memproses transaksi pembayaran keanggotaan atau jasa pendampingan.</li>
                            <li>Menghubungi Anda terkait status pengajuan atau revisi data.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">3</span>
                            Pembagian Data kepada Pihak Ketiga
                        </h3>
                        <p>
                            Kami <strong>TIDAK</strong> menjual data Anda kepada pihak manapun. Namun, untuk keperluan
                            layanan, kami akan meneruskan data Anda kepada:
                        </p>
                        <ul class="list-disc pl-12 mt-2 space-y-2">
                            <li><strong>Instansi Pemerintah:</strong> Badan Penyelenggara Jaminan Produk Halal (BPJPH),
                                Kementerian Agama, dan Dinas terkait untuk penerbitan sertifikat.</li>
                            <li><strong>Lembaga Pendamping (LP3H):</strong> Untuk proses verifikasi dan validasi lapangan.
                            </li>
                            <li><strong>Payment Gateway:</strong> Pihak penyedia layanan pembayaran untuk memproses
                                transaksi secara aman.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">4</span>
                            Keamanan Data
                        </h3>
                        <p>
                            Kami menerapkan langkah-langkah keamanan teknis yang wajar untuk melindungi data Anda dari akses
                            yang tidak sah. Transaksi pembayaran dilindungi menggunakan teknologi enkripsi (SSL) melalui
                            mitra Payment Gateway resmi.
                        </p>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">5</span>
                            Kontak Privasi
                        </h3>
                        <p>
                            Jika Anda memiliki pertanyaan mengenai penggunaan data Anda, silakan hubungi kami di:
                        </p>
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p><strong>Email:</strong> sabiliapps@gmail.com</p>
                            <p><strong>Alamat:</strong> Jl. R.H Mulya, Desa Pusakasari, Kec. Leles, Kab. Cianjur, Jawa Barat
                            </p>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </div>
@endsection
