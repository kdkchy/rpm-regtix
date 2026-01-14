<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $campaign = [
            'title' => 'SANGA SANGA RUN 7K RACE PACK COLLECTION',
            'event_id' => Event::first()->id,
            'status' => 'active',
            'subject' => '[REMINDER] SANGA SANGA RUN 7K RACE PACK COLLECTION',
            'html_template' => '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Race Pack - SANGA SANGA RUN 2025</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        h1, h2, p {
            margin: 0;
            padding: 0;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .container {
            background-color: #ffffff;
            color: #333;
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #333;
        }

        .header p {
            font-size: 1.2em;
            color: #6c757d;
        }

        .highlight {
            color: #007BFF;
            font-weight: bold;
        }

        .address, .important {
            font-style: italic;
            font-size: 1.1em;
        }

        .address a {
            font-weight: bold;
        }

        .content {
            margin-top: 30px;
            padding-left: 20px;
            padding-right: 20px;
        }

        .content h2 {
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .content ul {
            padding-left: 20px;
            list-style-type: square;
        }

        .content ul li {
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }

        .footer p {
            color: #007BFF;
        }

        .footer .date-time {
            font-size: 1.1em;
            color: #6c757d;
        }

        .highlight-date {
            font-weight: bold;
            font-size: 1.3em;
        }

        .spacer {
            margin-top: 20px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 600px) {
            .container {
                margin: 20px 10px;
                padding: 20px;
            }

            .header h1 {
                font-size: 1.8em;
            }

            .header p,
            .footer,
            .content h2,
            .highlight-date {
                font-size: 1em;
            }

            .content ul {
                padding-left: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Dear Runners,</h1>
        </div>

        <div class="content">
            <p>Terima kasih telah membeli tiket <span class="highlight">SANGA SANGA RUN 2025</span>. Untuk seluruh peserta yang sudah mendaftarkan diri, dihimbau untuk mengambil <span class="highlight">race pack</span> di:</p>
            
            <div class="spacer"></div>
            <h2>Lokasi Pengambilan Race Pack:</h2>
            <p class="address">
                <strong>MANGO LANGO LAKE</strong><br>
                Jl. Sawo Babakan Bitera, Gianyar, Bali<br>
                <a href="https://maps.app.goo.gl/r5Wy75gYNE41Etws7" target="_blank">Klik disini untuk lokasi Google Maps</a>
            </p>

            <div class="spacer"></div>
            <h2>Pada Tanggal:</h2>
            <p class="highlight-date">10-11 Mei 2025</p>
            <p class="highlight-date">11.00-19.00 WITA</p>

            <div class="spacer"></div>
            <h2>Mohon membaca syarat pengambilan race pack yang tertera dibawah ini:</h2>
            <ul>
                <li>Identitas Diri: KTP/SIM/Kartu Pelajar asli atau fotokopi.</li>
                <li>Konfirmasi Keikutsertaan: Cetakan atau digital bukti konfirmasi pendaftaran.</li>
                <li>Surat Persetujuan Orang Tua/Wali (Parental Consent): Untuk peserta di bawah 17 tahun.</li>
                <li>Surat Kuasa (jika diwakilkan): Surat kuasa yang ditandatangani peserta dan disertai fotokopi KTP peserta.</li>
                <li>Identitas Perwakilan (jika diwakilkan): KTP/SIM/Kartu Pelajar asli perwakilan.</li>
                <li>Race pack tidak dapat dititipkan/diambil saat race day/setelah race day.</li>
                <li>Race pack yang tidak diambil pada jadwal yang ditentukan akan menjadi hak Panitia.</li>
            </ul>
        </div>

        <div class="footer">
            <p>Sampai bertemu, Runners!</p>
            <p class="highlight">SANGA SANGA RUN 2025!</p>
            <p class="date-time">12.5.2025 | 06.00 WITA</p>
        </div>
    </div>

</body>
</html>
'
        ];

        Campaign::create($campaign);
    }
}
