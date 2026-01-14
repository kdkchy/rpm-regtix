<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Cetak Data Pendaftaran</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000;
      background: #fff !important;
    }

    @page {
      size: A4 portrait;
      margin: 1.5cm;
    }

    .print-container {
      width: 100%;
      max-width: 17.5cm;
      margin: 0 auto;
      padding: 0.3cm;
    }

    .header {
      text-align: center;
      margin-bottom: 1cm;
      border-bottom: 1px solid #000;
      padding-bottom: 0.3cm;
    }

    .pt-4 {
      padding-top: 0.3rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 0.5cm 0;
      line-height: 0.7rem;
    }

    table, th, td {
      border: 1px solid #000;
    }

    th, td {
      padding: 8px;
      text-align: left;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      /* Sembunyikan header browser */
      @page {
        margin: 0;
      }

      html, body {
        margin: 0 !important;
        padding: 0 !important;
      }

      body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
    }

    @media screen {
      .print-actions {
        text-align: center;
        margin: 1cm;
      }

      button {
        padding: 10px 20px;
        margin: 0 10px;
        cursor: pointer;
      }
    }
  </style>
</head>
<body>
  <!-- Tombol Aksi (Hanya di browser) -->
  <div class="print-actions no-print">
    <button onclick="openPrintDialog()">🖨️ Print</button>
    <button onclick="window.close()">✖ Close</button>
  </div>

  <!-- Konten Cetak -->
  <div class="print-container">
    <div class="header">
      <h2>DATA PENDAFTARAN</h2>
      <h3>{{ $registration->event->name }}</h3>
      <strong>ID Registrasi: {{ $registration->registration_code }}</strong>
    </div>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Informasi Pribadi</h3>
        <span style="font-size: 14px;"><strong>Validator:</strong> {{ $registration->validator->name }}</span>

        </div>
      <table>
        <tr><th width="30%">Nama Lengkap</th><td>{{ $registration->full_name }}</td></tr>
        <tr><th>Email</th><td>{{ $registration->email }}</td></tr>
        <tr><th>Nomor Telepon</th><td>{{ $registration->phone }}</td></tr>
        <tr><th>Jenis Kelamin</th><td>{{ $registration->gender }}</td></tr>
        <tr><th>Tempat Lahir</th><td>{{ $registration->place_of_birth }}</td></tr>
        <tr><th>Tanggal Lahir</th><td>{{ \Carbon\Carbon::parse($registration->dob)->format('d/m/Y') }}</td></tr>
        <tr><th>Alamat</th><td>{{ $registration->address }}</td></tr>
        <tr><th>Kecamatan</th><td>{{ $registration->district }}</td></tr>
        <tr><th>Provinsi</th><td>{{ $registration->province }}</td></tr>
        <tr><th>Negara</th><td>{{ $registration->country }}</td></tr>
        <tr><th>Kewarganegaraan</th><td>{{ $registration->nationality }}</td></tr>
        <tr><th>Golongan Darah</th><td>{{ $registration->blood_type }}</td></tr>
        <tr><th>Jenis Identitas</th><td>{{ $registration->id_card_type }}</td></tr>
        <tr><th>Nomor Identitas</th><td>{{ $registration->id_card_number }}</td></tr>
        <tr><th>Kontak Darurat</th><td>{{ $registration->emergency_contact_name }}</td></tr>
        <tr><th>No. Telp Darurat</th><td>{{ $registration->emergency_contact_phone }}</td></tr>
      </table>
      <div class="pt-4">
        <h3>Detail Pendaftaran</h3>
        <table>
            <tr><th width="30%">Tanggal Daftar</th><td>{{ $registration->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr>
                <th>Tipe Tiket</th>
                <td>
                    {{ $registration->ticketType->name }}
                    @if ($registration->ticketType->price > 0)
                        ({{ 'Rp. ' . number_format($registration->ticketType->price, 0, ',', '.') }})
                    @endif
                </td>
            </tr>
            <tr><th>Ukuran Jersey</th><td>{{ $registration->jersey_size }}</td></tr>
            <tr><th>Nama Komunitas</th><td>{{ $registration->community_name }}</td></tr>
            <tr><th>Nama di BIB</th><td>{{ $registration->bib_name }}</td></tr>
            <tr><th>Nomor BIB</th><td>{{ $registration->reg_id }}</td></tr>
        </table>
      </div>
      <div class="pt-4">
        <p><strong>Gianyar, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY') }}</strong></p>
        <br><br>
        <p style="border-top: 1px solid #000; width: 30%; margin-top: 3px; padding-bottom: 3px;"></p> 
        <p>{{ $registration->full_name }}</p>
      </div>
    </div>
  </div>
  <script>
    let printDialogOpened = false; // Flag to track if print dialog is opened

    function openPrintDialog() {
      if (!printDialogOpened) {
        printDialogOpened = true;

        // Menampilkan dialog cetak
        window.print();

        // Menutup tab setelah sedikit jeda (misalnya 3 detik) untuk memberi waktu penyimpanan PDF
        setTimeout(function() {
          window.close();
        }, 1000);  //1 detik untuk memberi waktu penyimpanan
      }
    }
  </script>
</body>
</html>
