<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDPA / Privacy Notice — HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-50 text-neutral-900">

    <header class="border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-semibold text-lg text-violet-800">HireSense</a>
            <nav class="flex gap-6 text-sm">
                <a href="{{ url('/') }}" class="hover:text-violet-700">← Back home</a>
            </nav>
        </div>
    </header>

    <section class="max-w-3xl mx-auto px-6 py-14">

        <div class="mb-1 text-xs font-semibold text-teal-700 uppercase tracking-wide">PDPA 2010 / Act 709</div>
        <h1 class="text-2xl font-bold mb-1">Privacy Notice / Notis Privasi</h1>
        <p class="text-sm text-neutral-500 mb-8">
            Job Application Portal — Personal Data Protection Notice.
        </p>

        <div class="flex gap-2 mb-6" role="tablist">
            <button type="button" onclick="showLang('en')" id="tab-en"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-violet-700 text-white">English</button>
            <button type="button" onclick="showLang('bm')" id="tab-bm"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100">Bahasa Malaysia</button>
        </div>

        <div id="lang-en" class="bg-white border border-neutral-200 rounded-xl p-8 space-y-6">

            <p class="text-sm text-neutral-600">Before you submit your job application, please read this notice.</p>

            <div>
                <h2 class="font-semibold mb-2">1. Data We Collect</h2>
                <p class="text-sm text-neutral-700">We collect information you provide during the application process, including (but not limited to) your name, phone number, email address, resume/CV, employment history, and any other information you submit.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">2. Purpose of Processing</h2>
                <p class="text-sm text-neutral-700 mb-2">Your data is used <strong>solely</strong> for:</p>
                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                    <li>Assessing your suitability for the position applied for</li>
                    <li>Contacting you regarding the interview/application process</li>
                    <li>Internal recruitment records</li>
                </ul>
                <p class="text-sm text-neutral-700 mt-2">If our system uses AI tools (e.g. for resume screening), processing by that tool is <strong>restricted</strong> to the purpose of evaluating this job application only.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">3. Data Sharing</h2>
                <p class="text-sm text-neutral-700">We will <strong>not</strong> sell or share your personal data with third parties (e.g. insurance companies, marketing agencies) without your separate consent, except as required by law.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">4. Data Retention Period</h2>
                <p class="text-sm text-neutral-700">Resumes and personal data of unsuccessful candidates will be retained for <strong class="bg-amber-100 px-1 rounded">[6 months / 1 year — please set]</strong> from the date of application, after which they will be automatically deleted or anonymised, unless you consent to longer retention (e.g. for a talent pool).</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">5. Your Rights</h2>
                <p class="text-sm text-neutral-700 mb-2">Under the PDPA 2010 (including the 2024 Amendment), you have the right to:</p>
                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                    <li>Access the personal data we hold about you</li>
                    <li>Correct inaccurate data</li>
                    <li><strong>Request deletion (right to be forgotten)</strong> of your personal data at any time</li>
                    <li>Withdraw your consent</li>
                </ul>
                <p class="text-sm text-neutral-700 mt-2">To make a request, please contact: <strong class="bg-amber-100 px-1 rounded">[PDPA contact/PIC email]</strong></p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">6. Data Security</h2>
                <p class="text-sm text-neutral-700">We take reasonable technical and organisational measures to protect your data from unauthorised access, loss, or misuse.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">7. Consent</h2>
                <p class="text-sm text-neutral-700">By ticking the box on the application form, you confirm that you have read and understood this notice, and you <strong>consent</strong> to the collection and processing of your personal data for the purposes stated above.</p>
            </div>

        </div>

        <div id="lang-bm" class="bg-white border border-neutral-200 rounded-xl p-8 space-y-6 hidden">

            <p class="text-sm text-neutral-600">Sebelum anda menghantar permohonan jawatan, sila baca notis ini.</p>

            <div>
                <h2 class="font-semibold mb-2">1. Data Yang Dikumpul</h2>
                <p class="text-sm text-neutral-700">Kami mengumpul maklumat yang anda berikan semasa proses permohonan, termasuk (tetapi tidak terhad kepada) nama, nombor telefon, alamat emel, resume/CV, sejarah pekerjaan, dan maklumat lain yang anda serahkan.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">2. Tujuan Pemprosesan</h2>
                <p class="text-sm text-neutral-700 mb-2">Data anda digunakan <strong>semata-mata</strong> untuk tujuan berikut:</p>
                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                    <li>Menilai kesesuaian anda untuk jawatan yang dipohon</li>
                    <li>Menghubungi anda berkaitan proses temuduga/permohonan</li>
                    <li>Rekod dalaman berkaitan proses pengambilan pekerja</li>
                </ul>
                <p class="text-sm text-neutral-700 mt-2">Jika sistem kami menggunakan alat AI (contohnya untuk menyaring resume), pemprosesan data oleh alat tersebut adalah <strong>tertutup</strong> dan hanya untuk tujuan penilaian jawatan ini sahaja.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">3. Perkongsian Data</h2>
                <p class="text-sm text-neutral-700">Kami <strong>tidak akan</strong> menjual atau berkongsi data peribadi anda dengan pihak ketiga (contoh: syarikat insurans, agensi pemasaran) tanpa kebenaran berasingan daripada anda, kecuali dikehendaki oleh undang-undang.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">4. Tempoh Simpanan Data</h2>
                <p class="text-sm text-neutral-700">Resume dan maklumat peribadi calon yang tidak berjaya akan disimpan selama <strong class="bg-amber-100 px-1 rounded">[6 bulan / 1 tahun — sila tetapkan]</strong> dari tarikh permohonan, selepas itu ia akan dipadam atau dinyahnamakan secara automatik, melainkan anda memberi kebenaran untuk simpanan lebih lama (contoh: talent pool).</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">5. Hak Anda</h2>
                <p class="text-sm text-neutral-700 mb-2">Di bawah PDPA 2010 (termasuk pindaan 2024), anda berhak untuk:</p>
                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                    <li>Mengakses data peribadi anda yang kami simpan</li>
                    <li>Membetulkan data yang tidak tepat</li>
                    <li><strong>Meminta pemadaman (right to be forgotten)</strong> data peribadi anda pada bila-bila masa</li>
                    <li>Menarik balik persetujuan anda</li>
                </ul>
                <p class="text-sm text-neutral-700 mt-2">Untuk membuat permintaan, sila hubungi: <strong class="bg-amber-100 px-1 rounded">[emel/kenalan PIC PDPA]</strong></p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">6. Keselamatan Data</h2>
                <p class="text-sm text-neutral-700">Kami mengambil langkah teknikal dan organisasi yang munasabah untuk melindungi data anda daripada capaian tanpa kebenaran, kehilangan, atau penyalahgunaan.</p>
            </div>

            <div>
                <h2 class="font-semibold mb-2">7. Persetujuan</h2>
                <p class="text-sm text-neutral-700">Dengan menandakan kotak pada borang permohonan, anda mengesahkan bahawa anda telah membaca dan memahami notis ini, dan anda <strong>bersetuju</strong> dengan pengumpulan dan pemprosesan data peribadi anda untuk tujuan yang dinyatakan di atas.</p>
            </div>

        </div>

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

    <script>
        function showLang(lang) {
            document.getElementById('lang-en').classList.toggle('hidden', lang !== 'en');
            document.getElementById('lang-bm').classList.toggle('hidden', lang !== 'bm');
            document.getElementById('tab-en').className = lang === 'en'
                ? 'px-4 py-2 rounded-lg text-sm font-medium bg-violet-700 text-white'
                : 'px-4 py-2 rounded-lg text-sm font-medium bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100';
            document.getElementById('tab-bm').className = lang === 'bm'
                ? 'px-4 py-2 rounded-lg text-sm font-medium bg-violet-700 text-white'
                : 'px-4 py-2 rounded-lg text-sm font-medium bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-100';
        }
    </script>

</body>
</html>
