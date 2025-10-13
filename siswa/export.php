<?php
session_start();
if (!isset($_SESSION['user'])) {
    die('Akses ditolak.');
}

require_once '../config.php';

// Ambil parameter filter
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';
$kelas_id_filter = isset($_GET['kelas_id']) ? $koneksi->real_escape_string($_GET['kelas_id']) : '';

// Bangun query
$query = "SELECT siswa.nis, siswa.nisn, siswa.nama, siswa.jenis_kelamin, kelas.nama_kelas
          FROM siswa 
          JOIN kelas ON siswa.kelas_id = kelas.id";
$where = [];
if ($keyword) $where[] = "siswa.nama LIKE '%$keyword%'";
if ($kelas_id_filter) $where[] = "siswa.kelas_id = '$kelas_id_filter'";
if ($where) $query .= " WHERE " . implode(' AND ', $where);
$query .= " ORDER BY siswa.nama ASC";

$result = $koneksi->query($query);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$type = $_GET['type'] ?? 'pdf';

// === EKSPOR KE EXCEL ===
if ($type === 'xlsx') {
    require_once '../vendor/autoload.php'; // Pastikan PHPSpreadsheet terinstal via Composer

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'No');
    $sheet->setCellValue('B1', 'NIS');
    $sheet->setCellValue('C1', 'NISN');
    $sheet->setCellValue('D1', 'Nama Siswa');
    $sheet->setCellValue('E1', 'Jenis Kelamin');
    $sheet->setCellValue('F1', 'Kelas');

    // Style header
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF25D366'); // Warna WhatsApp hijau
    $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');

    // Data
    $no = 1;
    foreach ($data as $row) {
        $sheet->setCellValue('A' . ($no + 1), $no);
        $sheet->setCellValue('B' . ($no + 1), $row['nis']);
        $sheet->setCellValue('C' . ($no + 1), $row['nisn']);
        $sheet->setCellValue('D' . ($no + 1), $row['nama']);
        $sheet->setCellValue('E' . ($no + 1), $row['jenis_kelamin']);
        $sheet->setCellValue('F' . ($no + 1), $row['nama_kelas']);
        $no++;
    }

    // Auto-size kolom
    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Header file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="data_siswa_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// === EKSPOR KE PDF ===
if ($type === 'pdf') {
    require_once '../vendor/tcpdf/tcpdf.php'; // Pastikan TCPDF terinstal

    class SiswaPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 10, 'DATA SISWA - SISTEM ABSENSI', 0, 1, 'C');
            $this->Ln(5);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 6, 'Dicetak pada: ' . date('d/m/Y H:i'), 0, 1, 'C');
            $this->Ln(5);
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new SiswaPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();

    // Tabel
    $pdf->SetFont('helvetica', 'B', 10);
    $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin', 'Kelas'];
    $w = [8, 20, 25, 60, 30, 35]; // Lebar kolom (total ~178mm, sesuai A4)

    // Header tabel
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Data
    $pdf->SetFont('helvetica', '', 9);
    $no = 1;
    foreach ($data as $row) {
        $pdf->Cell($w[0], 6, $no, 1, 0, 'C');
        $pdf->Cell($w[1], 6, $row['nis'], 1, 0, 'L');
        $pdf->Cell($w[2], 6, $row['nisn'], 1, 0, 'L');
        $pdf->Cell($w[3], 6, $row['nama'], 1, 0, 'L');
        $pdf->Cell($w[4], 6, $row['jenis_kelamin'], 1, 0, 'C');
        $pdf->Cell($w[5], 6, $row['nama_kelas'], 1, 0, 'C');
        $pdf->Ln();
        $no++;
    }

    if (empty($data)) {
        $pdf->Cell(0, 10, 'Tidak ada data siswa.', 1, 1, 'C');
    }

    $pdf->Output('data_siswa_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}

die('Jenis ekspor tidak valid.');