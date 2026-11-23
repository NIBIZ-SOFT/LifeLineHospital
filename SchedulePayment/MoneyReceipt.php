<?php
// ============================================================
// MONEY RECEIPT  (CrVoucher -> Sales -> Customer / Product / Project)
// Same receipt as the first page, printed 2 times on one A4 (Customer + Office copy)
// ============================================================

// ---------- helpers ----------

/** Escape for HTML output */
function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** First non-empty value among the given keys of a row, else $default */
function pick($row, array $keys, $default = '')
{
    if (!is_array($row)) {
        return $default;
    }
    foreach ($keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
            return $row[$k];
        }
    }
    return $default;
}

/** Single row from a table, or empty array */
function fetchRow($table, $idField, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        return array();
    }
    $row = SQL_Select($table, "{$idField}={$id}", "", true);
    return is_array($row) ? $row : array();
}

/** One label + value row */
function field($label, $value, $flex = 1, $style = '')
{
    return '<span class="l">' . $label . '</span>'
         . '<div class="f" style="flex:' . $flex . ';' . $style . '">' . e($value) . '</div>';
}

// ---------- load data ----------

$voucherID = (int)$_REQUEST["ID"];

$rows    = SQL_Select("CrVoucher where CrVoucherID={$voucherID}");
$voucher = !empty($rows[0]) ? $rows[0] : array();

$settings = SQL_Select("Settings", "SettingsID=1", "", true);
$sale     = fetchRow("Sales",    "SalesID",    pick($voucher, array('SalesID')));
$customer = fetchRow("Customer", "CustomerID", pick($sale, array('CustomerID')));
$product  = fetchRow("Products", "ProductsID",  pick($sale, array('ProductID')));
$project  = fetchRow("Category", "CategoryID",
                     pick($voucher, array('ProjectID'), pick($sale, array('ProjectID'))));

// ---------- receipt values ----------

$amount      = (float)pick($voucher, array('Amount'), 0);
$fileNo   = pick($voucher, array('CrVoucherID'));

$ts          = strtotime(pick($voucher, array('Date')));
$dateText    = $ts ? date('d-m-Y', $ts) : '';
$dd          = $ts ? date('d', $ts) : '';
$mm          = $ts ? date('m', $ts) : '';
$yy          = $ts ? date('Y', $ts) : '';

$customerName    = pick($customer, array('CustomerName', 'Name'));
$customerAddress = pick($customer, array('Address', 'PresentAddress', 'PermanentAddress'));

$productType = pick($product, array('ProductType'));
$flatType    = pick($product, array('FlatType'));
$flatSize    = pick($product, array('BuildArea'));
$floorNo     = pick($product, array('FloorNumber'));

// "Against" text
$against = $flatType;
if ($productType === 'FlatAppartment' && $floorNo !== '') {
    $against .= ' (Floor ' . $floorNo . ')';
}

// Share count only makes sense for share products
$shareCount = in_array($productType, array('LandShare', 'HotelShare'))
    ? pick($sale, array('NoOfShare', 'ShareQty', 'Quantity'), pick($product, array('NoOfShare', 'ShareQty')))
    : '';

// $customerNo = (int)pick($customer, array('CustomerNo'), 0);
$customerNo = str_pad($customer['CustomerNo'], 4, '0', STR_PAD_LEFT);

$bankName = pick($voucher, array('BankCashName'));
$branch   = pick($voucher, array('Branch', 'BranchName'));
$remarks  = pick($voucher, array('Description'));
$projectName = pick($project, array('Name'));

// Payment Type (Booking Money / Instalment)
// CHECK FIELD NAME: taken from actualsalsepayment.Term (same source the old page showed as "Against").
// If it shows blank or a wrong value, change 'Term' below to your real column name.
$payRow      = SQL_Select("actualsalsepayment", "MRRNO={$voucherID}", "", true);
$paymentType = pick($payRow, array('Term', 'PaymentType'), pick($voucher, array('PaymentType', 'Term')));

// ---------- WhatsApp values ----------
// NOTE: change the field names below to match your Customer table
$custPhone = preg_replace('/\D/', '', (string)pick($customer, array('Mobile', 'Phone', 'ContactNo', 'Cell')));
if ($custPhone !== '' && substr($custPhone, 0, 2) !== '88') {
    $custPhone = '88' . ltrim($custPhone, '0');   // Bangladesh country code
}
$waFileName = 'MoneyReceipt-' . $customerNo . '.pdf';

// ---------- company header (Settings table) ----------

$logo    = !empty($settings['logo']) ? './upload/' . e($settings['logo']) : '';
$logoImg = $logo ? '<img src="' . $logo . '" alt="">' : '';
$wmImg   = $logo ? '<img class="wm" src="' . $logo . '" alt="">' : '';

$contact = array();
if (!empty($settings['Address'])) {
    $contact[] = '<span class="red"><span class="dot">&#9679;</span> অফিস :</span> ' . nl2br(e($settings['Address']));
}
if (!empty($settings['Phone'])) {
    $contact[] = '<span class="red"><span class="dot">&#9679;</span> হটলাইন :</span> ' . e($settings['Phone']);
}
$contact[] = '<span class="dot">&#9679;</span> ' . e(pick($settings, array('Website'), 'www.lifeline.hospital'));
if (!empty($settings['Email'])) {
    $contact[] = '<span class="dot">&#9679;</span> ' . e($settings['Email']);
}
$contactHtml = '<div>' . implode('</div><div>', $contact) . '</div>';

// ---------- one receipt (printed twice below) ----------

ob_start();
?>
<div class="receipt">
    <?= $wmImg ?>

    <div class="header">
        <div class="brand"><?= $logoImg ?></div>
        <div class="contact"><?= $contactHtml ?></div>
        <div class="titlebox">
            <div class="stripes"></div>
            <div class="title">MONEY<br>RECEIPT</div>
            <div class="copy-label">{{COPY}}</div>
        </div>
    </div>

    <div class="band">
        <span class="lbl">Recipt No</span>
        <div class="box rno"><?= e($fileNo) ?></div>
        <span style="flex:1"></span>
        <span class="lbl">Date</span>
        <div class="box dt"><?= e($dd) ?></div>
        <div class="box dt"><?= e($mm) ?></div>
        <div class="box dt y"><?= e($yy) ?></div>
        <span class="lbl">File No</span>
        <div class="box fileno"><?= e($customerNo) ?></div>
    </div>

    <div class="rows">
        <div class="r"><?= field('Name of Purchaser (s)', $customerName) ?></div>

        <div class="r"><?= field('Address', $customerAddress) ?></div>

        <div class="r">
            <?= field('Received with thanks a sum of Tk', BangladeshiCurencyFormat($amount) . '/-') ?>
            <?= field('In Words', NumberToWordsNew($amount), 1.4, 'font-size:10px;text-transform:capitalize') ?>
        </div>

        <div class="r">
            <?= field('Date', $dateText) ?>
            <?= field('Bank', $bankName, 1.2) ?>
        </div>

        <div class="r">
            <?= field('Branch', $branch) ?>
            <?= field('Payment Type', $paymentType) ?>
            <?= field('Against', $against, 1.2) ?>
        </div>

        <div class="r">
            <?= field('Category of Ownership', $productType) ?>
            <?= field('No. of Share', $shareCount) ?>
            <?= field('Size (SFT)', $flatSize) ?>
        </div>

        <div class="r">
            <?= field('Project', $projectName) ?>
            <?= field('Remarks (If any)', $remarks, 1.3) ?>
        </div>
    </div>

    <div class="sign prep">Prepared by</div>
    <div class="sign auth">Authorised Signature</div>
    <div class="bar bar-l"></div>
    <div class="bar bar-r"></div>
</div>
<?php
$receiptHtml = ob_get_clean();

// ---------- output ----------

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Money Receipt</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Segoe UI",Arial,"Nirmala UI","Siyam Rupali",sans-serif;background:#ececec;
     display:flex;flex-direction:column;align-items:center;padding:20px;
     -webkit-print-color-adjust:exact;print-color-adjust:exact}
.receipt{width:210mm;height:148mm;position:relative;overflow:hidden;flex:none;
         background:linear-gradient(180deg,#f7fbfa,#edf5f2);color:#3f5a55;font-size:12px}

/* dashed cutting line between the two copies (1mm: 148 + 1 + 148 = 297mm) */
.cut-line{position:relative;width:210mm;height:1mm;flex:none}
.cut-line:after{content:"";position:absolute;left:0;right:0;top:50%;border-top:1px dashed #333}

/* header */
.header{position:relative;z-index:2;height:33mm;background:#fff;display:flex;align-items:center;gap:6mm;padding:0 12mm 0 10mm}
.brand img{display:block;height:21mm;max-width:82mm;width:auto}
.contact{flex:1;min-width:0;font-size:9px;line-height:1.6;color:#222}
.contact .red{color:#b4402c;font-weight:bold}
.contact .dot{color:#2f6f69}
.titlebox{position:relative;width:34mm;height:100%}
.stripes{position:absolute;right:2mm;top:0;width:26mm;height:11mm;
         background:repeating-linear-gradient(90deg,#8fbcae 0,#8fbcae 1.4px,#dcebe5 1.4px,#dcebe5 3.2px)}
.title{position:absolute;right:0;top:13mm;font-size:29px;line-height:.92;font-weight:bold;color:#5a3b36;
       transform:scaleX(.82);transform-origin:right top;letter-spacing:-.5px}

.copy-label{position:absolute;right:0;bottom:1mm;font-size:9px;font-weight:700;text-transform:uppercase;
            color:#c0543a;border:1px solid #c0543a;padding:.4mm 2mm;border-radius:2mm;white-space:nowrap}

/* band */
.band{position:relative;z-index:2;height:12mm;background:#c9ddd2;border-top:1px solid #a7c3b6;
      border-bottom:1px solid #a7c3b6;display:flex;align-items:center;padding:0 8mm 0 10mm;gap:4mm}
.lbl{font-size:13px;color:#555}
.band .lbl, .band .box{flex-shrink:0;white-space:nowrap}
.box{background:#e6efea;border:1px solid #d6e2dc;height:8mm;display:flex;align-items:center;
     justify-content:center;font-weight:bold;font-size:14px;color:#111}
.rno{font-size:13px;letter-spacing:1px;width:auto;min-width:24mm;padding:0 3mm;white-space:nowrap;flex-shrink:0;color:#4a4a4a;font-weight:700;background:#dbe6e0;border-color:#b9c9c1}
.dt{width:12mm}.dt.y{width:20mm}
.fileno{width:42mm;justify-content:flex-start;padding-left:3mm;font-size:13px;font-weight:normal;color:#333}

/* rows */
.rows{position:relative;z-index:2;padding-top:3mm}
.r{display:flex;align-items:flex-end;height:9mm;padding:0 8mm 0 10mm;gap:2mm}
.l{white-space:nowrap;font-size:13px;color:#3f5a55}
.f{flex:1;border-bottom:1px solid #7fa59b;height:6mm;padding:0 2mm 1px;font-weight:bold;
   font-size:12px;color:#000;white-space:nowrap;overflow:hidden}

/* watermark + footer */
.wm{position:absolute;right:18mm;top:42mm;width:78mm;opacity:.09;z-index:1}
.sign{position:absolute;bottom:13mm;border-top:1px solid #4f6b66;padding-top:1mm;font-size:13px;color:#3f5a55;z-index:2}
.prep{left:10mm;width:34mm}
.auth{right:8mm;width:48mm;text-align:right}
.bar{position:absolute;bottom:0;height:8mm;z-index:1}
.bar-l{left:0;width:72%;background:#2f6f69}
.bar-r{right:0;width:33%;background:#c0543a;clip-path:polygon(14% 0,100% 0,100% 100%,0 100%)}

/* toolbar buttons (screen only) */
.toolbar{position:fixed;top:10px;right:14px;z-index:99;display:flex;gap:8px}
.tbtn{border:0;color:#fff;padding:9px 16px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;
      box-shadow:0 1px 4px rgba(0,0,0,.25)}
.tbtn.print{background:#2f6f69}
.tbtn.wa{background:#25d366}
.tbtn:disabled{opacity:.6;cursor:wait}

@media print{
  @page{size:A4 portrait;margin:0}
  html,body{margin:0!important;padding:0!important;background:#fff!important;width:210mm;height:297mm}
  body{display:block}
  .receipt,.cut-line{margin:0!important;page-break-inside:avoid;page-break-after:avoid}
  .cut-line{background:#fff}
  .toolbar{display:none!important}
}
</style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="tbtn print" onclick="window.print()">🖨 Print</button>
    <button type="button" class="tbtn wa" id="waBtn" onclick="shareWhatsApp()">WhatsApp</button>
</div>

<?= str_replace('{{COPY}}', 'Customer Copy', $receiptHtml) ?>
<div class="cut-line"></div>
<?= str_replace('{{COPY}}', 'Office Copy', $receiptHtml) ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
var WA_PHONE = <?= json_encode($custPhone) ?>;
var WA_FILE  = <?= json_encode($waFileName) ?>;

async function shareWhatsApp() {
    var btn = document.getElementById('waBtn');
    btn.disabled = true; btn.textContent = 'Preparing...';
    try {
        // sends only the first receipt (one copy)
        var el = document.querySelector('.receipt');
        var canvas = await html2canvas(el, {scale: 2, useCORS: true, backgroundColor: '#ffffff'});
        var pdf = new window.jspdf.jsPDF({orientation: 'landscape', unit: 'mm', format: [210, 148]});
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 210, 148);
        var blob = pdf.output('blob');
        var file = new File([blob], WA_FILE, {type: 'application/pdf'});
        var text = 'Money Receipt';

        // Phones / supported browsers: opens the share sheet with the PDF attached
        if (navigator.canShare && navigator.canShare({files: [file]})) {
            await navigator.share({files: [file], title: text, text: text});
        } else {
            // Desktop fallback: download the PDF, then open WhatsApp chat to attach it
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = WA_FILE;
            document.body.appendChild(a); a.click(); a.remove();
            var url = 'https://wa.me/' + (WA_PHONE || '') + '?text=' + encodeURIComponent(text);
            setTimeout(function () { window.open(url, '_blank'); }, 600);
            alert('PDF downloaded. Attach it in the WhatsApp chat that just opened.');
        }
    } catch (err) {
        if (err && err.name !== 'AbortError') alert('Could not prepare the receipt: ' + err.message);
    } finally {
        btn.disabled = false; btn.textContent = 'WhatsApp';
    }
}
</script>
</body>
</html>
<?php
$MainContent .= ob_get_clean();