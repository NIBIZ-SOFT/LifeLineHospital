<?php
// ============================================================
// MONEY RECEIPT  (CrVoucher -> Sales -> Customer / Product / Project)
// With auto-generated QR code (top right) containing all receipt data
// Header shows: Office (Address), Regional Office, Corporate Office
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

/** One office line: coloured tag + address text */
function officeRow($tag, $text, $cls = '')
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }
    // keep line breaks the user typed, but collapse to a single line feel
    $text = nl2br(e($text));
    return '<div class="ofc ' . $cls . '"><span class="tag">' . $tag . '</span>'
         . '<span class="txt">' . $text . '</span></div>';
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

$amount   = (float)pick($voucher, array('Amount'), 0);
$fileNo   = str_pad(pick($voucher, array('CrVoucherID')), 4, '0', STR_PAD_LEFT);

$ts       = strtotime(pick($voucher, array('Date')));
$dateText = $ts ? date('d-m-Y', $ts) : '';
$dd       = $ts ? date('d', $ts) : '';
$mm       = $ts ? date('m', $ts) : '';
$yy       = $ts ? date('Y', $ts) : '';

$customerName    = pick($customer, array('CustomerName', 'Name'));
$customerAddress = pick($customer, array('Address', 'PresentAddress', 'PermanentAddress'));

$productType = pick($product, array('ProductType'));
$flatType    = pick($product, array('FlatType'));
$flatSize    = pick($product, array('BuildArea'));
$floorNo     = pick($product, array('FloorNumber'));

// "Against" text
$against = $productType;
if ($productType === 'FlatAppartment' && $floorNo !== '') {
    $against .= ' (Floor ' . $floorNo . ')';
}


// Share count only makes sense for share products
$shareCount = in_array($productType, array('LandShare', 'HotelShare'))
    ? pick($sale, array('NoOfShare', 'ShareQty', 'Quantity'), pick($product, array('NoOfShare', 'ShareQty')))
    : '';

$customerNo = str_pad(pick($customer, array('CustomerNo')), 4, '0', STR_PAD_LEFT);

$bankName    = pick($voucher, array('BankCashName'));
$branch      = pick($voucher, array('Branch', 'BranchName'));
$remarks     = pick($voucher, array('Description'));
$projectName = pick($project, array('Name'));

// Prepared by (user who saved the voucher; falls back to the logged-in user)
$preparedBy = pick($voucher, array('PreparedBy', 'CreatedBy', 'EntryBy', 'AddedBy', 'UserName'));
if ($preparedBy === '' && !empty($_SESSION)) {
    $preparedBy = pick($_SESSION, array('UserName', 'Username', 'user_name', 'FullName', 'Name'));
}

// Payment Type (Booking Money / Instalment)
$payRow      = SQL_Select("actualsalsepayment", "MRRNO={$voucherID}", "", true);
$paymentType = pick($payRow, array('Term', 'PaymentType'), pick($voucher, array('PaymentType', 'Term')));

// ---------- WhatsApp values ----------
$custPhone = preg_replace('/\D/', '', (string)pick($customer, array('Mobile', 'Phone', 'ContactNo', 'Cell')));
if ($custPhone !== '' && substr($custPhone, 0, 2) !== '88') {
    $custPhone = '88' . ltrim($custPhone, '0');   // Bangladesh country code
}

// CHANGED: WhatsApp PDF name is now the full Customer No only (e.g. "LL-0012.pdf"),
//          no more "MoneyReceipt-" prefix. Characters not allowed in file names
//          (/ \ : * ? " < > |) are replaced with "-" so the download/share never fails.
//          If the customer has no CustomerNo, it falls back to the padded $customerNo.
$fullCustomerNo = trim((string)pick($customer, array('CustomerNo')));
$fullCustomerNo = preg_replace('/[\\\\\/:*?"<>|\s]+/', '-', $fullCustomerNo);
$waFileName     = ($fullCustomerNo !== '' ? $fullCustomerNo : $customerNo) . '.pdf';

// ---------- company header (Settings table) ----------

$logo    = !empty($settings['logo']) ? './upload/' . e($settings['logo']) : '';
$logoImg = $logo ? '<img src="' . $logo . '" alt="">' : '';
$wmImg   = $logo ? '<img class="wm" src="' . $logo . '" alt="">' : '';

// Authorised signature image
$sigFile = __DIR__ . '/signature.png';
$sigData = file_exists($sigFile)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($sigFile))
    : './CrVoucher/signature.png';
$sigImg  = '<img class="sig-img" src="' . $sigData . '" alt="">';

// Offices (Settings columns: Address, RegionalOffice, CorporateOffice)
$officeHtml  = '';
$officeHtml .= officeRow('হাসপাতালের ঠিকানা',           pick($settings, array('Address')),         'o1');
$officeHtml .= officeRow('কর্পোরেট অফিস',   pick($settings, array('CorporateOffice')), 'o3');
$officeHtml .= officeRow('আঞ্চলিক অফিস',   pick($settings, array('RegionalOffice')),  'o2');

// Hotline / web / email in one compact line
$meta = array();
$phone   = pick($settings, array('Phone'), '01910818016,01332851560');
$website = pick($settings, array('Website', 'WebSite'), 'www.lifeline.hospital');
$email   = pick($settings, array('Email'), 'lifelinehospitalbd@gmail.com');

if (!empty($phone)) {
    $meta[] = '<span class="m"><b class="ic">&#9742;</b> <span class="red">হটলাইন:</span> ' . e($phone) . '</span>';
}
if (!empty($website)) {
    $meta[] = '<span class="m"><b class="ic">&#9679;</b> ' . e($website) . '</span>';
}
if (!empty($email)) {
    $meta[] = '<span class="m"><b class="ic">&#9993;</b> ' . e($email) . '</span>';
}
$metaHtml = '<div class="meta">' . implode('', $meta) . '</div>';

$contactHtml = '<div class="offices">' . $officeHtml . '</div>' . $metaHtml;

// ---------- QR code data (everything on the receipt) ----------

// CHANGED: login URL is now built dynamically instead of hard-coded, so it keeps
//          working if the domain, folder (/LLHERP) or theme changes.
//          - scheme + host + script path come from the current request
//          - Theme comes from the current page (falls back to "default")
//          - Base is fixed to "System" and Script to "login", because the login
//            page always lives in the System base, even when this receipt is
//            opened from another module.
$qrScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$qrTheme  = !empty($_REQUEST['Theme']) ? $_REQUEST['Theme'] : 'default';
$loginUrl = $qrScheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']
          . '?Theme=' . urlencode($qrTheme) . '&Base=System&Script=login';


// CHANGED: QR contains ONLY the login URL. To restore receipt details in the QR,
// un-comment the $qrLines blocks above and use: $qrText = implode("\n", $qrLines);

// $qrLines = array(
//     'MONEY RECEIPT',
//     'Receipt No: '   . $fileNo,
//     'File No: '      . $customerNo,
//     'Date: '         . $dateText,
//     'Purchaser: '    . $customerName,
//     'Address: '      . $customerAddress,
//     'Amount: Tk '    . BangladeshiCurencyFormat($amount) . '/-',
//     'In Words: '     . NumberToWordsNew($amount),
//     'Bank: '         . $bankName,
//     'Branch: '       . $branch,
//     'Payment Type: ' . $paymentType,
//     'Against: '      . $against,
//     'Ownership: '    . $productType,
//     'No. of Share: ' . $shareCount,
//     'Size (SFT): '   . $flatSize,
//     'Project: '      . $projectName,
//     'Remarks: '      . $remarks,
//     'Prepared by: '  . $preparedBy,
// );
// $qrLines = array_filter($qrLines, function ($l) {
//     return !preg_match('/:\s*$/', $l);
// });

// $qrText = $loginUrl; to $qrText = implode("\n", $qrLines);

$qrText = $loginUrl;

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
     display:flex;justify-content:center;align-items:flex-start;padding:20px;
     -webkit-print-color-adjust:exact;print-color-adjust:exact}
.receipt{width:210mm;height:148mm;position:relative;overflow:hidden;
         background:linear-gradient(180deg,#f7fbfa,#edf5f2);color:#3f5a55;font-size:12px}

/* header */
.header{position:relative;z-index:2;height:37mm;background:#fff;display:flex;align-items:center;gap:3mm;padding:0 5mm 0 6mm;
        border-bottom:1px solid #d6e2dc}
.brand{flex-shrink:0}
.brand img{display:block;height:22mm;max-width:44mm;width:auto}

/* offices block */
.contact{flex:1;min-width:0;display:flex;flex-direction:column;gap:1mm;
         border-left:2px solid #2f6f69;padding-left:3mm}
.offices{display:flex;flex-direction:column;gap:1mm}
.ofc{display:flex;align-items:flex-start;gap:2mm;font-size:8.5px;line-height:1.35;color:#222}
.ofc .tag{flex:0 0 23mm;text-align:center;color:#fff;font-weight:bold;font-size:7.5px;line-height:1.3;
          padding:.5mm 1mm;border-radius:2px;white-space:nowrap}
.ofc.o1{font-size:10px}
.ofc.o1 .tag{background:#2f6f69;font-size:8.5px}
.ofc.o1 .txt{font-weight:bold;color:#111}
.ofc.o2 .tag{background:#4f8f87}
.ofc.o3 .tag{background:#b4402c}
.ofc .txt{flex:1;min-width:0;padding-top:.3mm}

/* hotline / web / email */
.meta{display:flex;align-items:center;flex-wrap:nowrap;gap:0 2.5mm;border-top:1px dashed #b9c9c1;padding-top:1mm;
      font-size:7.5px;line-height:1.25;color:#222;white-space:nowrap}
.meta .m{white-space:nowrap;flex-shrink:0}
.meta .red{color:#b4402c;font-weight:bold}
.meta .ic{color:#2f6f69;font-weight:normal}

.titlebox{position:relative;width:28mm;height:100%;flex-shrink:0}
.stripes{position:absolute;right:2mm;top:0;width:24mm;height:11mm;
         background:repeating-linear-gradient(90deg,#8fbcae 0,#8fbcae 1.4px,#dcebe5 1.4px,#dcebe5 3.2px)}
.title{position:absolute;right:13px;top:13mm;font-size:25px;line-height:.92;font-weight:bold;color:#5a3b36;
       transform:scaleX(.82);transform-origin:right top;letter-spacing:-.5px}

/* QR code (top right) */
.qr{width:24mm;height:24mm;flex-shrink:0;background:#fff;padding:1mm;border:1px solid #d6e2dc}
.qr img,.qr canvas{width:100%!important;height:100%!important;display:block}

/* band */
.band{position:relative;z-index:2;height:11mm;background:#c9ddd2;border-top:1px solid #a7c3b6;
      border-bottom:1px solid #a7c3b6;display:flex;align-items:center;padding:0 8mm 0 10mm;gap:4mm}
.lbl{font-size:13px;color:#555}
.band .lbl, .band .box{flex-shrink:0;white-space:nowrap}
.box{background:#e6efea;border:1px solid #d6e2dc;height:7.5mm;display:flex;align-items:center;
     justify-content:center;font-weight:bold;font-size:14px;color:#111}
.rno{font-size:13px;letter-spacing:1px;width:auto;min-width:24mm;padding:0 3mm;white-space:nowrap;flex-shrink:0;color:#4a4a4a;font-weight:700;background:#dbe6e0;border-color:#b9c9c1}
.dt{width:12mm}.dt.y{width:20mm}
.fileno{width:42mm;justify-content:flex-start;padding-left:3mm;font-size:13px;font-weight:normal;color:#333}

/* rows */
.rows{position:relative;z-index:2;padding-top:2mm}
.r{display:flex;align-items:flex-end;height:8.6mm;padding:0 8mm 0 10mm;gap:2mm}
.l{white-space:nowrap;font-size:13px;color:#3f5a55}
.f{flex:1;border-bottom:1px solid #7fa59b;height:6mm;padding:0 2mm 1px;font-weight:bold;
   font-size:12px;color:#000;white-space:nowrap;overflow:hidden}

/* watermark + footer */
.wm{position:absolute;right:18mm;top:46mm;width:78mm;opacity:.09;z-index:1}
.sign{position:absolute;bottom:13mm;border-top:1px solid #4f6b66;padding-top:1mm;font-size:13px;color:#3f5a55;z-index:2}
.prep{left:10mm;width:48mm}
.pname{position:absolute;left:0;right:0;bottom:100%;margin-bottom:1mm;font-weight:bold;font-size:12px;color:#000;
       white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.auth{right:8mm;width:48mm;text-align:right}
.auth .sig-img{position:absolute;left:0;right:0;bottom:100%;margin:0 auto 1mm;height:9mm;max-width:46mm;
               object-fit:contain;display:block;pointer-events:none}
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
  .receipt{margin:0!important;page-break-inside:avoid;page-break-after:avoid}
  .toolbar{display:none!important}
}
</style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="tbtn print" onclick="window.print()">🖨 Print</button>
    <button type="button" class="tbtn wa" id="waBtn" onclick="shareWhatsApp()">WhatsApp</button>
</div>

<div class="receipt">
    <?= $wmImg ?>

    <div class="header">
        <div class="brand"><?= $logoImg ?></div>
        <div class="contact"><?= $contactHtml ?></div>
        <div class="titlebox">
            <div class="stripes"></div>
            <div class="title">MONEY<br>RECEIPT</div>
        </div>
        <div class="qr" id="qrcode"></div>
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
            <?= field('Category of Ownership', $flatType) ?>
            <?= field('No. of Share', $shareCount) ?>
            <?= field('Size (SFT)', $flatSize) ?>
        </div>

        <div class="r">
            <?= field('Project', $projectName) ?>
            <?= field('Remarks (If any)', $remarks, 1.3) ?>
        </div>
    </div>

    <div class="sign prep"><div class="pname"><?= e($preparedBy) ?></div>Prepared by</div>
    <div class="sign auth"><?= $sigImg ?>Authorised Signature</div>
    <div class="bar bar-l"></div>
    <div class="bar bar-r"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
var QR_TEXT  = <?= json_encode($qrText, JSON_UNESCAPED_UNICODE) ?>;
var WA_PHONE = <?= json_encode($custPhone) ?>;
var WA_FILE  = <?= json_encode($waFileName) ?>;

// ---------- QR code ----------
window.addEventListener('load', function () {
    try {
        new QRCode(document.getElementById('qrcode'), {
            text: QR_TEXT,
            width: 220,
            height: 220,
            correctLevel: QRCode.CorrectLevel.M
        });
    } catch (err) {
        document.getElementById('qrcode').style.display = 'none';
    }
});

// ---------- WhatsApp share ----------
async function shareWhatsApp() {
    var btn = document.getElementById('waBtn');
    btn.disabled = true; btn.textContent = 'Preparing...';
    try {
        var el = document.querySelector('.receipt');
        var canvas = await html2canvas(el, {scale: 2, useCORS: true, backgroundColor: '#ffffff'});
        var pdf = new window.jspdf.jsPDF({orientation: 'landscape', unit: 'mm', format: [210, 148]});
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 210, 148);
        var blob = pdf.output('blob');
        var file = new File([blob], WA_FILE, {type: 'application/pdf'});
        var text = 'Money Receipt';

        if (navigator.canShare && navigator.canShare({files: [file]})) {
            await navigator.share({files: [file], title: text, text: text});
        } else {
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