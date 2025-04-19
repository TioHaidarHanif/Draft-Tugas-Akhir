Ok intinya aku mau ngapain sih?
Tiket disini maksutnya catatan terstruktur dari permintaan dukungan, laporan masalah, pertanyaan atau insiden yan diajukan oleh pelanggan
Aku mau nge buat project make backend laravel untuk membuat sebuah sistem helpdesk yang siinya itu sejenis ticketing. Intinya aplikasianya digunakan untuk manajemen keluhan, misal dari user tuh ada keluhan tentang sesuatu yang berkaitan dengan instansi atau divisi tertentu, nanti dia komplain lewat sistem helpdesk ini. Nah dengan sisten hepdesek ini nanti si user bisa tau progeerss dari keluhan ini udah sampai mana diselesaikannya. Terus kalo dari sisi admin, nanti admin bisa ngelola keluhananya, entah di jawab, atau di tindak lanjuti atau di minta perjelas lagi kelihannya dan lain sebagainya. Intinya sih supaya kendalannya terselesaikan. 
NAh untuk project ini buatnya make apa?, buatnya tuh make laravel, tapi kalo aku pribadi buatnya cukup di APIO nya saja, sisianya nanti di handle sama frontend. 
Kalau misal kayak gitu, kan buatnya make SPL. SPL nih supaya kita buat core aplikasinya dulu, jadi kita buat apliaksi generallnya dulu terkait helpdesk, nanti waktu udah jadi aplikasi generallnya tinggal di sesuain sama kebutuhanm tiap instansi,misal LAAK telyu atau admisi. Jadi langskah langkah nya itu
Melakukan domain analisys -> requirement general
Domain engineering -> apliaksi general
Requirement analisys -> requirement dari instansi
Product derivation -> apliaksi yang sesuai kebutuhan instyansi
Ok dalm domain analisys, yang dilakukan adlaah saya mempelajari sistem yang sudah ada, sistem helpdesk yang sudah ada terkait apa saja fitur yang ada pada sistem helpdesk secara umum terutama pada sistem helpdesk adalah sebagai berikut 
Makanjeemn Tiket
Manajemen tiket ini befungsi untuk mengelola tiket, baik dari user atau dari admin. Secara umum manajjemen tiket ini terdiri dari CRUD tiket, pelacakan status tiket, progres tiket, riwayat interaksi, menanggapi tiket, status tiket (misal udah beres atau belum, atau ditutup), komentar tiket, kategori tiket, penugasan tiket (misalnya siapa yang bertanggung jawab atau yang serharunya menyelesaikan tiket ini) dan lain sebagainya
 
Kalau dari sisi user, biasanya user mah sekedar membuat tiket, membatalkan tiket, memberi tambahan informasi tiket, melihat status atau progress dari tiket, melihat log tiket, memfollow up tiket. Tapi kalau admin, dia bisa mengelola tiket. Mengubah status tiket, meminta pengguna untuk mengupdate tiket, atau memberi tanggapan, atau memecah tiket atau menggabungkan tiket lalu mungkin nanti melihat monitoring statistik tiket, misal dari tiket yang ada, mana aja yang udah diselesaikan dan mana aja yang belum di selesaikan. Jadi bisa keliatan statsitiknya, menindaklanjuti juga, meprioritaskan ticket nya juga. Oh iy, admin bisa aja menolak tiketnya kalo dirasa kurang relevan. Di balik layar sebeenernya admin nanti nge minta ke instansi terkait untuk nge beresin problemnya, “eh ini ada masalah, tolong beresin”. Entah lewat WA atau lewat mana. Terus nanti dari PIC yang dituju nya nge beresin, terus si admin bakal nge follow up ke pic kalo misal tugasnya belum beres. Terus nanti adminnya bisa nge update status dari pengerjaannya udah sampai mana. Tapi ini lewat perintah komentar dulu sih



Manajemen FAQ

Untuk FAQ, sebenernya ini simple, misal admin bisa CRUD FAQ, tapi kalau user cuman bisa liat, paling nanti bisa dikelompokin juga terkait FAQ nya

Manajemen User
Untuk manajemen user, admin tuh nanti bisa ngelola siapa aja yang jadi admin, role admin nya juga, misal mau ada role siapa aja. Kalau user mah biasa bisa login loghout register( basic auth), bisa edit profil juga., tapi kalau admin nanti bisa CRUD akun, terus bisa nambah role dll. Bisa juga nge hapus atau lainnya

Ok ittu yang umum nya, untuk variabilitas nya nanti bisa macem macem
Apa aja?
Database, misal bisa SQLite atau bisa make MySQL
Login, bisa menggunakan email biasa atau mau menggunakan google
Mau ada FAQ atau tidak 
Kmeudian notifikasi ke pengguna, yang wajib itu di aplikasinya ada notif nya, tapi yang engga wajib tuh ada di email, itu kita bisa mau make email atau engga ngasih notifnya, atau telegram (itu jika sudah terhubung)
Ketegori ticket, priioritas tiket

