<?php
// ─── utils/PdfGenerator.php ──────────────────────────────────

class PdfGenerator {

    public static function generateString(array $n, array $articles): string {
        if (class_exists('\Mpdf\Mpdf')) return self::withMpdf($n, $articles);
        if (class_exists('TCPDF'))      return self::withTcpdf($n, $articles);
        throw new \Exception('No PDF library. Run: composer require tecnickcom/tcpdf');
    }

    private static function withMpdf(array $n, array $articles): string {
        $mpdf = new \Mpdf\Mpdf([
            'mode'           => 'utf-8',
            'format'         => 'A4',
            'margin_top'     => 0,
            'margin_bottom'  => 0,
            'margin_left'    => 0,
            'margin_right'   => 0,
        ]);
        $mpdf->SetTitle($n['newsletter_title'] ?? 'Newsletter');
        $mpdf->WriteHTML(self::buildHtml($n, $articles));
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private static function withTcpdf(array $n, array $articles): string {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('NMS');
        $pdf->SetTitle($n['newsletter_title'] ?? 'Newsletter');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(true, 0);
        $pdf->AddPage();
        $pdf->writeHTML(self::buildHtml($n, $articles), true, false, true, false, '');
        return $pdf->Output('newsletter.pdf', 'S');
    }

    // ── Main HTML builder ────────────────────────────────────────
    public static function buildHtml(array $n, array $articles): string {

        $title    = self::e($n['newsletter_title']   ?? 'Newsletter');
        $org      = self::e($n['organization_name']  ?? '');
        $dept     = self::e($n['department']         ?? '');
        $edition  = self::e($n['edition']            ?? '');
        $date     = self::e($n['publish_date']       ?? '');
        $editor   = self::e($n['editor_name']        ?? '');
        $headline = self::e($n['headline']           ?? '');
        $intro    = self::p($n['intro_content']      ?? '');
        $contact  = self::e($n['contact_email']      ?? '');
        $website  = self::e($n['website']            ?? '');

        // ── COVER PAGE ───────────────────────────────────────────
        $cover = "
        <!-- ======= COVER PAGE ======= -->
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#0f1f4b;'>
          <tr>
            <!-- Gold left accent bar -->
            <td width='8' style='background:#c8962a;'>&nbsp;</td>
            <td style='padding:0;'>

              <!-- Top meta strip -->
              <table width='100%' cellpadding='0' cellspacing='0' style='background:#c8962a;'>
                <tr>
                  <td style='padding:7px 30px;font-family:Arial,sans-serif;font-size:9px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#0f1f4b;'>$org</td>
                  <td style='padding:7px 30px;font-family:Arial,sans-serif;font-size:9px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#0f1f4b;text-align:center;'>$date</td>
                  <td style='padding:7px 30px;font-family:Arial,sans-serif;font-size:9px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#0f1f4b;text-align:right;'>Edition: $edition</td>
                </tr>
              </table>

              <!-- Masthead -->
              <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                  <td style='padding:40px 40px 10px;text-align:center;border-bottom:2px solid rgba(255,255,255,0.15);'>
                    <p style='font-family:Arial,sans-serif;font-size:10px;letter-spacing:4px;text-transform:uppercase;color:#f0c060;margin:0 0 12px;'>$org</p>
                    <h1 style='font-family:Georgia,serif;font-size:42px;font-weight:900;color:#ffffff;margin:0 0 14px;line-height:1.1;letter-spacing:-1px;'>$title</h1>
                    <p style='font-family:Arial,sans-serif;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.55);margin:0;border-top:1px solid rgba(255,255,255,0.2);border-bottom:1px solid rgba(255,255,255,0.2);padding:7px 0;display:inline-block;'>
                      $dept" .
                      ($dept && $editor ? " &nbsp;&nbsp;|&nbsp;&nbsp; Editor: $editor" : ($editor ? "Editor: $editor" : '')) .
                      ($edition ? " &nbsp;&nbsp;|&nbsp;&nbsp; $edition" : '') . "
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Headline & Intro -->
              " . ($headline || $intro ? "
              <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                  <td style='padding:24px 44px 30px;'>
                    " . ($headline ? "<p style='font-family:Georgia,serif;font-size:20px;font-style:italic;color:#f0c060;margin:0 0 12px;line-height:1.4;'>$headline</p>" : '') . "
                    " . ($intro    ? "<p style='font-family:Arial,sans-serif;font-size:12px;line-height:1.8;color:rgba(255,255,255,0.78);margin:0;'>$intro</p>" : '') . "
                  </td>
                </tr>
              </table>" : '') . "

            </td>
          </tr>
        </table>";

        // ── ARTICLES ─────────────────────────────────────────────
        $articlesBlock = '';
        if (!empty($articles)) {
            $articleRows = '';
            foreach ($articles as $i => $a) {
                $aTitle   = self::e($a['title']   ?? '');
                $aAuthor  = self::e($a['author']  ?? '');
                $aContent = self::p($a['content'] ?? '');

                // Resolve images
                $imgHtml = '';
                $imgList = [];
                if (!empty($a['images']) && is_array($a['images'])) {
                    $imgList = $a['images'];
                } elseif (!empty($a['image'])) {
                    $decoded = json_decode($a['image'], true);
                    $imgList = is_array($decoded) ? $decoded : [$a['image']];
                }
                // Use only existing local files
                $validImgs = [];
                foreach ($imgList as $u) {
                    $path = UPLOAD_DIR . basename($u);
                    if (file_exists($path)) $validImgs[] = $path;
                }
                if (count($validImgs) === 1) {
                    $imgHtml = "<img src='{$validImgs[0]}' style='width:100%;max-height:200px;object-fit:cover;display:block;margin-bottom:10px;border-radius:3px;'>";
                } elseif (count($validImgs) > 1) {
                    $cols = '';
                    $w = round(100 / count($validImgs)) . '%';
                    foreach ($validImgs as $p) {
                        $cols .= "<td style='padding:2px;'><img src='$p' style='width:100%;height:130px;object-fit:cover;border-radius:3px;display:block;'></td>";
                    }
                    $imgHtml = "<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:10px;'><tr>$cols</tr></table>";
                }

                // Separator before each article except first
                $sep = $i > 0 ? "<tr><td colspan='2' style='padding:0 40px;'><hr style='border:none;border-top:1px dashed #ccc;margin:0;'></td></tr>" : '';

                $articleRows .= "
                $sep
                <tr>
                  <td style='padding:" . ($i===0?'24px':'20px') . " 40px 20px;vertical-align:top;'>
                    <p style='font-family:Arial,sans-serif;font-size:9px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#c8962a;margin:0 0 4px;'>Article " . ($i+1) . "</p>
                    <h3 style='font-family:Georgia,serif;font-size:19px;font-weight:700;color:#0f1f4b;margin:0 0 5px;line-height:1.25;'>$aTitle</h3>
                    <p style='font-family:Arial,sans-serif;font-size:10px;font-style:italic;color:#999;margin:0 0 12px;padding-left:9px;border-left:3px solid #c8962a;'>By $aAuthor</p>
                    $imgHtml
                    <p style='font-family:Georgia,serif;font-size:13px;line-height:1.85;color:#333;margin:0;'>$aContent</p>
                  </td>
                </tr>";
            }

            $articlesBlock = "
            <!-- ======= ARTICLES ======= -->
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#fff;'>
              <tr>
                <td style='padding:0 40px;'>
                  <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                      <td style='padding:20px 0 6px;'>
                        <table width='100%' cellpadding='0' cellspacing='0'>
                          <tr>
                            <td style='border-top:3px solid #0f1f4b;width:30px;'>&nbsp;</td>
                            <td style='padding:0 12px;white-space:nowrap;font-family:Arial,sans-serif;font-size:10px;font-weight:bold;letter-spacing:3px;text-transform:uppercase;color:#0f1f4b;'>Articles</td>
                            <td style='border-top:3px solid #0f1f4b;'>&nbsp;</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              $articleRows
            </table>";
        }

        // ── ACHIEVEMENTS ─────────────────────────────────────────
        $achBlock = '';
        if (!empty($n['faculty_achievements']) || !empty($n['student_achievements'])) {
            $faTd = $saTd = '';
            if (!empty($n['faculty_achievements'])) {
                $fa = self::p($n['faculty_achievements']);
                $faTd = "<td width='50%' style='vertical-align:top;padding-right:10px;'>
                  <div style='background:#f7f5f0;border-top:4px solid #c8962a;padding:14px;border-radius:3px;height:100%;'>
                    <p style='font-family:Georgia,serif;font-size:12px;font-weight:bold;color:#0f1f4b;margin:0 0 8px;'>&#127891; Faculty Achievements</p>
                    <p style='font-family:Arial,sans-serif;font-size:11px;line-height:1.7;color:#444;margin:0;'>$fa</p>
                  </div></td>";
            }
            if (!empty($n['student_achievements'])) {
                $sa = self::p($n['student_achievements']);
                $saTd = "<td width='50%' style='vertical-align:top;padding-left:10px;'>
                  <div style='background:#f7f5f0;border-top:4px solid #c8962a;padding:14px;border-radius:3px;height:100%;'>
                    <p style='font-family:Georgia,serif;font-size:12px;font-weight:bold;color:#0f1f4b;margin:0 0 8px;'>&#127942; Student Achievements</p>
                    <p style='font-family:Arial,sans-serif;font-size:11px;line-height:1.7;color:#444;margin:0;'>$sa</p>
                  </div></td>";
            }
            $achBlock = self::sectionWrap('Achievements', "
              <table width='100%' cellpadding='0' cellspacing='0'><tr>$faTd$saTd</tr></table>");
        }

        // ── EVENTS ───────────────────────────────────────────────
        $evBlock = '';
        if (!empty($n['upcoming_events'])) {
            $ev = self::p($n['upcoming_events']);
            $evBlock = self::sectionWrap('Upcoming Events', "
              <div style='background:#fffdf5;border-left:5px solid #c8962a;padding:16px 20px;border-radius:3px;'>
                <p style='font-family:Arial,sans-serif;font-size:12px;line-height:1.8;color:#444;margin:0;'>$ev</p>
              </div>");
        }

        // ── HOD MESSAGE ──────────────────────────────────────────
        $hodBlock = '';
        if (!empty($n['hod_message'])) {
            $hod = self::p($n['hod_message']);
            $hodBlock = "
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#fff;'>
              <tr><td style='padding:0 40px 28px;'>
                <div style='background:#0f1f4b;padding:28px 32px;border-radius:3px;'>
                  <p style='font-family:Arial,sans-serif;font-size:9px;letter-spacing:3px;text-transform:uppercase;color:#c8962a;margin:0 0 12px;font-weight:bold;'>Message from HOD</p>
                  <blockquote style='font-family:Georgia,serif;font-size:15px;font-style:italic;color:rgba(255,255,255,0.88);line-height:1.75;margin:0 0 14px;padding:0;'>$hod</blockquote>
                  <p style='font-family:Arial,sans-serif;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:#c8962a;margin:0;'>&#8212; Head of Department, $dept</p>
                </div>
              </td></tr>
            </table>";
        }

        // ── CONTACT ROW ──────────────────────────────────────────
        $contactBlock = '';
        if ($contact || $website) {
            $contactBlock = "
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#f7f5f0;border-top:3px solid #c8962a;'>
              <tr>
                <td style='padding:18px 40px;font-family:Georgia,serif;font-size:14px;font-weight:700;color:#0f1f4b;'>$org</td>
                <td style='padding:18px 40px;text-align:right;font-family:Arial,sans-serif;font-size:11px;color:#666;'>
                  " . ($contact ? "&#128231; $contact" : '') . "
                  " . ($contact && $website ? " &nbsp;&nbsp;|&nbsp;&nbsp; " : '') . "
                  " . ($website ? "&#127760; $website" : '') . "
                </td>
              </tr>
            </table>";
        }

        // ── FOOTER ───────────────────────────────────────────────
        $footer = "
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#0f1f4b;'>
          <tr>
            <td style='padding:12px 40px;font-family:Arial,sans-serif;font-size:9px;color:rgba(255,255,255,0.45);'>$org &nbsp;&middot;&nbsp; $title</td>
            <td style='padding:12px 40px;font-family:Arial,sans-serif;font-size:9px;color:rgba(255,255,255,0.45);text-align:right;'>$date</td>
          </tr>
        </table>";

        return "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:Arial,sans-serif; background:#fff; font-size:13px; color:#222; }
  @page { margin: 0; }
  @media print {
    * { -webkit-print-color-adjust:exact!important; print-color-adjust:exact!important; }
    body { background:#fff!important; }
    .no-print { display:none!important; }
  }
</style>
</head>
<body>
$cover
$articlesBlock
$achBlock
$evBlock
$hodBlock
$contactBlock
$footer
</body>
</html>";
    }

    // ── Section wrapper helper ───────────────────────────────────
    private static function sectionWrap(string $label, string $content): string {
        return "
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#fff;'>
          <tr><td style='padding:0 40px;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
              <tr><td style='padding:18px 0 10px;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                  <tr>
                    <td style='border-top:3px solid #0f1f4b;width:30px;'>&nbsp;</td>
                    <td style='padding:0 12px;white-space:nowrap;font-family:Arial,sans-serif;font-size:10px;font-weight:bold;letter-spacing:3px;text-transform:uppercase;color:#0f1f4b;'>$label</td>
                    <td style='border-top:3px solid #0f1f4b;'>&nbsp;</td>
                  </tr>
                </table>
              </td></tr>
            </table>
          </td></tr>
          <tr><td style='padding:0 40px 24px;'>$content</td></tr>
        </table>";
    }

    // ── Helpers ──────────────────────────────────────────────────
    private static function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    private static function p(string $str): string {
        return nl2br(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
    }
}