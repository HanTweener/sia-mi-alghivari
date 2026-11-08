<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Hasil Belajar - {{ $studentName }}</title>
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
            size: a4 portrait;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #1c1917;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header-title {
            text-align: center;
        }

        .header-title h2 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        .header-title h3 {
            margin: 2px 0;
            font-size: 11pt;
            font-weight: bold;
        }

        .header-title p {
            margin: 2px 0 0 0;
            font-size: 8pt;
            color: #44403c;
        }

        .doc-title {
            text-align: center;
            margin: 10px 0 14px 0;
        }

        .doc-title h4 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 9pt;
            font-weight: bold;
        }

        .identity-table {
            width: 100%;
            margin-bottom: 14px;
            font-size: 9pt;
        }

        .identity-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9pt;
        }

        .data-table th, .data-table td {
            border: 1px solid #44403c;
            padding: 5px 6px;
        }

        .data-table th {
            background-color: #f5f5f4;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .section-heading {
            font-size: 9pt;
            font-weight: bold;
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .note-box {
            border: 1px solid #44403c;
            padding: 8px 10px;
            margin-bottom: 14px;
            min-height: 40px;
            font-size: 9pt;
        }

        .note-box-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        .signatures-table {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
            font-size: 9pt;
        }

        .signatures-table td {
            vertical-align: top;
            text-align: center;
            padding: 0 10px;
        }

        .signature-space {
            height: 55px;
        }

        .footer-notice {
            margin-top: 15px;
            font-size: 7.5pt;
            color: #78716c;
            text-align: right;
            border-top: 1px dotted #d6d3d1;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <!-- Header / Kop Dokumen Resmi Madrasah -->
    <table class="header-table">
        <tr>
            <td class="header-title">
                <h2>{{ $schoolName }}</h2>
                <h3>LAPORAN HASIL BELAJAR SISWA</h3>
                <p>{{ $schoolAddress }} • NPSN: {{ $schoolNpsn }}</p>
            </td>
        </tr>
    </table>

    <!-- Dokumen Title & Periode -->
    <div class="doc-title">
        <h4>RAPOR SEMESTER</h4>
        <p>{{ $academicYear }} — Semester {{ $semester }} ({{ $termName }})</p>
    </div>

    <!-- Identitas Siswa -->
    <table class="identity-table">
        <tr>
            <td style="width: 18%;">Nama Peserta Didik</td>
            <td style="width: 2%;">:</td>
            <td style="width: 40%; font-weight: bold;">{{ $studentName }}</td>
            <td style="width: 16%;">Kelas / Rombel</td>
            <td style="width: 2%;">:</td>
            <td style="width: 22%; font-weight: bold;">Kelas {{ $className }}</td>
        </tr>
        <tr>
            <td>Nomor Induk Siswa (NIS)</td>
            <td>:</td>
            <td>{{ $studentNis }}</td>
            <td>Semester</td>
            <td>:</td>
            <td>{{ $semester }} ({{ $termName }})</td>
        </tr>
        <tr>
            <td>NISN</td>
            <td>:</td>
            <td>{{ $studentNisn ?: '-' }}</td>
            <td>Tahun Ajaran</td>
            <td>:</td>
            <td>{{ $academicYear }}</td>
        </tr>
    </table>

    <!-- Bagian A: Capaian Hasil Belajar Per Mata Pelajaran -->
    <div class="section-heading">A. Capaian Hasil Belajar</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 44%;">Mata Pelajaran</th>
                <th style="width: 14%;">KKM</th>
                <th style="width: 16%;">Nilai Akhir</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item['subject_snapshot']['name'] ?? 'Mata Pelajaran' }}
                        <span style="font-size: 8pt; color: #57534e;">({{ $item['subject_snapshot']['code'] ?? '-' }})</span>
                    </td>
                    <td class="text-center font-bold">{{ $item['kkm_snapshot'] }}</td>
                    <td class="text-center font-bold">
                        {{ $item['final_score'] !== null ? $item['final_score'] : '-' }}
                    </td>
                    <td class="text-center" style="font-size: 8.5pt;">
                        {{ $item['kkm_status'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 15px; color: #78716c;">
                        Belum ada data mata pelajaran pada semester ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Bagian B: Rekapitulasi Presensi Kehadiran Per Mata Pelajaran -->
    <div class="section-heading">B. Rekapitulasi Presensi Per Mata Pelajaran</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 36%;">Mata Pelajaran</th>
                <th style="width: 9%;">Hadir</th>
                <th style="width: 9%;">Izin</th>
                <th style="width: 9%;">Sakit</th>
                <th style="width: 9%;">Alpa</th>
                <th style="width: 11%;">Pertemuan</th>
                <th style="width: 11%;">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totPresent = 0;
                $totExcused = 0;
                $totSick = 0;
                $totAbsent = 0;
                $totSessions = 0;
            @endphp
            @forelse($items as $index => $item)
                @php
                    $att = $item['attendance'] ?? [
                        'present' => 0,
                        'excused' => 0,
                        'sick' => 0,
                        'absent' => 0,
                        'total_sessions' => 0,
                        'rate' => null,
                    ];
                    $totPresent += ($att['present'] ?? 0);
                    $totExcused += ($att['excused'] ?? 0);
                    $totSick += ($att['sick'] ?? 0);
                    $totAbsent += ($att['absent'] ?? 0);
                    $totSessions += ($att['total_sessions'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['subject_snapshot']['name'] ?? 'Mata Pelajaran' }}</td>
                    <td class="text-center">{{ $att['present'] ?? 0 }}</td>
                    <td class="text-center">{{ $att['excused'] ?? 0 }}</td>
                    <td class="text-center">{{ $att['sick'] ?? 0 }}</td>
                    <td class="text-center">{{ $att['absent'] ?? 0 }}</td>
                    <td class="text-center">{{ $att['total_sessions'] ?? 0 }}</td>
                    <td class="text-center font-bold">
                        {{ $att['rate'] !== null ? $att['rate'] . '%' : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 10px; color: #78716c;">
                        Belum ada sesi presensi mata pelajaran.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($items) > 0)
            <tfoot>
                <tr style="background-color: #fafaf9; font-weight: bold;">
                    <td colspan="2" class="text-center">Total Akumulasi</td>
                    <td class="text-center">{{ $totPresent }}</td>
                    <td class="text-center">{{ $totExcused }}</td>
                    <td class="text-center">{{ $totSick }}</td>
                    <td class="text-center">{{ $totAbsent }}</td>
                    <td class="text-center">{{ $totSessions }}</td>
                    <td class="text-center">
                        @if($totSessions > 0)
                            {{ number_format(($totPresent / $totSessions) * 100, 2) }}%
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(isset($extracurriculars) && count($extracurriculars) > 0)
        <!-- Bagian Tambahan: Kegiatan Ekstrakurikuler -->
        <div class="section-heading">C. Kegiatan Ekstrakurikuler</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">No</th>
                    <th style="width: 28%; text-align: left;">Kegiatan Ekstrakurikuler</th>
                    <th style="width: 16%; text-align: center;">Predikat</th>
                    <th style="width: 50%; text-align: left;">Keterangan / Capaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($extracurriculars as $index => $extra)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-weight: bold;">{{ $extra->activity_name }}</td>
                        <td class="text-center font-bold">
                            {{ $extra->predicate }} ({{ $extra->predicate_label }})
                        </td>
                        <td>{{ $extra->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($p5Assessments) && count($p5Assessments) > 0)
        <!-- Bagian Tambahan: Asesmen Kokurikuler P5-PRA -->
        <div class="section-heading">D. Projek Penguatan Profil Pelajar Pancasila & Rahmatan Lil Alamin (P5-PRA)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">No</th>
                    <th style="width: 28%; text-align: left;">Projek P5-PRA</th>
                    <th style="width: 24%; text-align: left;">Dimensi Profil</th>
                    <th style="width: 14%; text-align: center;">Capaian</th>
                    <th style="width: 28%; text-align: left;">Catatan Perkembangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($p5Assessments as $index => $p5)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-weight: bold;">{{ $p5->project_title }}</td>
                        <td>{{ $p5->dimension }}</td>
                        <td class="text-center font-bold">
                            {{ $p5->predicate }}<br>
                            <small style="font-size: 7.5pt; color: #57534e;">({{ $p5->predicate_label }})</small>
                        </td>
                        <td>{{ $p5->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Catatan Wali Kelas -->
    <div class="note-box">
        <div class="note-box-title">Catatan Wali Kelas:</div>
        <div>
            {{ $homeroomNote ?: 'Pertahankan prestasi belajar, tingkatkan kedisiplinan dan terus kembangkan potensi diri.' }}
        </div>
    </div>

    <!-- Titimangsa & Pengesahan Tanda Tangan -->
    <table class="signatures-table">
        <tr>
            <td style="width: 33%;">
                Mengetahui,<br>
                Orang Tua / Wali Siswa
                <div class="signature-space"></div>
                <strong>( .................................................... )</strong>
            </td>
            <td style="width: 33%;">
                <!-- Ruang Kosong Tengah -->
            </td>
            <td style="width: 34%;">
                Bandung, {{ $datePublished }}<br>
                Wali Kelas
                <div class="signature-space"></div>
                <strong>{{ $homeroomTeacherName }}</strong><br>
                @if($homeroomTeacherNip)
                    <span>NIP: {{ $homeroomTeacherNip }}</span>
                @else
                    <span>NIP: -</span>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 15px;">
                Mengetahui,<br>
                Kepala Madrasah
                <div class="signature-space"></div>
                <strong>{{ $principalName }}</strong><br>
                @if($principalNip)
                    <span>NIP: {{ $principalNip }}</span>
                @else
                    <span>NIP: -</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Footer metadata & version -->
    <div class="footer-notice">
        Dokumen Laporan Hasil Belajar Resmi • Rev #{{ $revision }} • Token: {{ substr($sourceVersion, 0, 16) }}... • Dicetak secara digital
    </div>

</body>
</html>
