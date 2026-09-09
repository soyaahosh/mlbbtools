// apidata.js
const API_DATA = [
     {
        method: "GET",
        path: "/api/AlightMotion",
        title: "Alight Motion Services",
        actions: [
            { tag: "send", label: "Send Link", desc: "Kirim verification link ke email tujuan.", params: [
                { name: "action", fixed: "send", type: "string" },
                { name: "email", req: true, type: "string", ph: "user@gmail.com" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "verify", label: "Verify Link", desc: "Verifikasi link yang diterima dari email.", params: [
                { name: "action", fixed: "verify", type: "string" },
                { name: "email", req: true, type: "string", ph: "user@gmail.com" },
                { name: "link", req: true, type: "string", ph: "link_from_email" },
                { name: "orderid", req: false, type: "string", ph: "GPA.1234-5678-9012-34567" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "bulk", label: "Bulk Email Generator", desc: "Generate beberapa email premium sekaligus dengan streaming real-time. Orderid bisa lebih dari satu, pisahkan dengan koma.", params: [
                { name: "action", fixed: "bulk", type: "string" },
                { name: "amount", req: true, type: "integer", ph: "10" },
                { name: "orderid", req: false, type: "string", ph: "GPA.1111-1111-1111-11111,GPA.2222-2222-2222-22222" },
                { name: "stream", req: false, type: "boolean", ph: "true" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/validateGame",
        title: "Game Validator (Multi Game)",
        actions: [
            { tag: "roblox", label: "Roblox", desc: "Validasi username Roblox.", params: [
                { name: "game", fixed: "roblox", type: "string" },
                { name: "username", req: true, type: "string", ph: "Username" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "genshin", label: "Genshin Impact", desc: "Validasi UID Genshin Impact.", params: [
                { name: "game", fixed: "genshin", type: "string" },
                { name: "uid", req: true, type: "string", ph: "123456789" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "pubg", label: "PUBG Mobile", desc: "Validasi UID PUBG Mobile.", params: [
                { name: "game", fixed: "pubg", type: "string" },
                { name: "uid", req: true, type: "string", ph: "123456789" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "ff", label: "Free Fire", desc: "Validasi UID Free Fire.", params: [
                { name: "game", fixed: "ff", type: "string" },
                { name: "uid", req: true, type: "string", ph: "123456789" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "codm", label: "COD Mobile", desc: "Validasi UID COD Mobile.", params: [
                { name: "game", fixed: "codm", type: "string" },
                { name: "uid", req: true, type: "string", ph: "12345678912345" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/validateMLBB",
        title: "MLBB Complete Checker",
        actions: [
            { tag: "lookup", label: "Full Profile Lookup", desc: "Cek profil lengkap akun MLBB.", params: [
                { name: "action", fixed: "lookup", type: "string" },
                { name: "userID", req: true, type: "string", ph: "1421402291" },
                { name: "serverID", req: true, type: "string", ph: "16001" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "nickcek", label: "Check Nickname & Region", desc: "Cek username dan region akun MLBB.", params: [
                { name: "action", fixed: "nickcek", type: "string" },
                { name: "userID", req: true, type: "string", ph: "12345678" },
                { name: "serverID", req: true, type: "string", ph: "1234" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "dbcek", label: "Check Diamond & Bundle", desc: "Cek status double diamond dan bundle aktif.", params: [
                { name: "action", fixed: "dbcek", type: "string" },
                { name: "userID", req: true, type: "string", ph: "12345678" },
                { name: "serverID", req: true, type: "string", ph: "1234" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "bindcek", label: "Check Bind Status", desc: "Cek status bind akun (sosmed/email/dll).", params: [
                { name: "action", fixed: "bindcek", type: "string" },
                { name: "userID", req: true, type: "string", ph: "12345678" },
                { name: "serverID", req: true, type: "string", ph: "1234" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/validateMCGG",
        title: "Magic Chess Go Go Checker",
        actions: [
            { tag: "check", label: "Check MCGG Account", desc: "Cek nickname, region, dan recharge bonus MCGG.", params: [
                { name: "userID", req: true, type: "string", ph: "84836" },
                { name: "serverID", req: true, type: "string", ph: "1003" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/checkGtc",
        title: "Getcontact Checker",
        actions: [
            { tag: "check", label: "Check Getcontact Name", desc: "Cari nama dari nomor telepon via Getcontact.", params: [
                { name: "q", req: true, type: "string", ph: "62812345678" },
                { name: "key", req: true, type: "string", ph: "your_key" },
                { name: "token", req: true, type: "string", ph: "your_token" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/checkEwallet",
        title: "E-Wallet Checker",
        actions: [
            { tag: "checkAll", label: "Check All E-Wallets", desc: "Cek semua e-wallet: DANA, GOPAY, OVO, SHOPEEPAY, GRAB.", params: [
                { name: "nomor", req: true, type: "string", ph: "62812345678" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "dana", label: "Check DANA", desc: "Cek registrasi nomor di DANA.", params: [
                { name: "nomor", req: true, type: "string", ph: "62812345678" },
                { name: "wallet", fixed: "dana", type: "string" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "gopay", label: "Check GOPAY", desc: "Cek registrasi nomor di GOPAY.", params: [
                { name: "nomor", req: true, type: "string", ph: "62812345678" },
                { name: "wallet", fixed: "gopay", type: "string" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "ovo", label: "Check OVO", desc: "Cek registrasi nomor di OVO.", params: [
                { name: "nomor", req: true, type: "string", ph: "62812345678" },
                { name: "wallet", fixed: "ovo", type: "string" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "shopeepay", label: "Check SHOPEEPAY", desc: "Cek registrasi nomor di SHOPEEPAY.", params: [
                { name: "nomor", req: true, type: "string", ph: "62812345678" },
                { name: "wallet", fixed: "shopeepay", type: "string" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/checkKeyStatus",
        title: "Check API Key Status",
        actions: [
            { tag: "check", label: "Check API Key Status", desc: "Cek validitas dan tanggal kadaluarsa API Key.", params: [
                { name: "apiKey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "POST",
        path: "/api/OrkutMutasi",
        title: "OrderKuota QRIS Mutasi",
        actions: [
            { tag: "login", label: "Login OrderKuota", desc: "Login dengan username dan password OrderKuota.", params: [
                { name: "action", fixed: "login", type: "string" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "password", req: true, type: "string", ph: "your_password" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "otp", label: "Verify OTP", desc: "Verifikasi OTP untuk mendapatkan Auth Token.", params: [
                { name: "action", fixed: "otp", type: "string" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "otp", req: true, type: "string", ph: "123456" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "profile", label: "Cek Profile", desc: "Cek detail Profile kalian.", params: [
                { name: "action", fixed: "profile", type: "string" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "authToken", req: true, type: "string", ph: "2207472:xxxxx" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "mutasi", label: "Check QRIS Mutations", desc: "Ambil daftar transaksi QRIS terbaru.", params: [
                { name: "action", fixed: "mutasi", type: "string" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "authToken", req: true, type: "string", ph: "2207472:xxxxx" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "createqr", label: "Create Dynamic QRIS", desc: "Buat QRIS dinamis dengan nominal tertentu.", params: [
                { name: "action", fixed: "createqr", type: "string" },
                { name: "qrString", req: true, type: "string", ph: "000201010211..." },
                { name: "nominal", req: true, type: "integer", ph: "10000" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "withdraw", label: "QRIS Withdraw", desc: "Tarik saldo QRIS (minimal 1000, kelipatan 1000).", params: [
                { name: "action", fixed: "withdraw", type: "string" },
                { name: "username", req: true, type: "string", ph: "your_username" },
                { name: "authToken", req: true, type: "string", ph: "2207472:xxxxx" },
                { name: "amount", req: true, type: "integer", ph: "1000" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "POST",
        path: "/api/Wink-ai",
        title: "Wink.ai Image/Video Enhancer",
        actions: [
            { tag: "image", label: "Enhance Image", desc: "Upload gambar untuk enhancement, respons langsung.", params: [
                { name: "action", fixed: "image", type: "string" },
                { name: "file", req: true, type: "file", ph: "Pilih file gambar" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]},
            { tag: "video", label: "Enhance Video", desc: "Upload video untuk enhancement, proses async.", params: [
                { name: "action", fixed: "video", type: "string" },
                { name: "file", req: true, type: "file", ph: "Pilih file video" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    },
    {
        method: "GET",
        path: "/api/Wink-ai",
        title: "Wink.ai Status Checker",
        actions: [
            { tag: "status", label: "Check Status", desc: "Polling status pemrosesan video dari task ID.", params: [
                { name: "action", fixed: "status", type: "string" },
                { name: "taskId", req: true, type: "string", ph: "abc-123-def" },
                { name: "apikey", req: true, type: "string", ph: "YOUR_API_KEY" }
            ]}
        ]
    }
];

module.exports = API_DATA;