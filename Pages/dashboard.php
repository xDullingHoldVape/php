<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require 'D:\XAMPP\htdocs\PHPFinalTask\AutoLoader\autoload.php';
use App\Services\PasswordRepository;
use App\Services\EncryptionService;
use App\Services\PasswordGenerator;

$userId  = $_SESSION['user_id'];
$rawKey  = base64_decode($_SESSION['raw_key']);
$enc     = new EncryptionService();
$pwdRepo = new PasswordRepository();
$message = '';
$msgType = '';

// Handle actions

// Delete a saved password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($pwdRepo->delete($id, $userId)) {
        $message = 'Entry deleted.';
        $msgType = 'success';
    }
}

// Save a new password entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $siteName     = trim($_POST['site_name']     ?? '');
    $siteUsername = trim($_POST['site_username'] ?? '');
    $plainPwd     = $_POST['plain_password']      ?? '';
    $notes        = trim($_POST['notes']          ?? '');

    if ($siteName === '' || $plainPwd === '') {
        $message = 'Site name and password are required.'; $msgType = 'error';
    } else {
        $encPwd   = $enc->encrypt($plainPwd, $rawKey);
        $encNotes = $notes !== '' ? $enc->encrypt($notes, $rawKey) : '';
        $pwdRepo->create($userId, $siteName, $siteUsername, $encPwd, $encNotes);
        $message = 'Password saved!'; $msgType = 'success';
    }
}

// Generate password via AJAX (returns JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $mode      = $_POST['mode'] ?? 'count';
    $generator = new PasswordGenerator();

    if ($mode === 'percent') {
        $len  = max(4, (int)($_POST['total_length'] ?? 12));
        $upP  = (float)($_POST['pct_upper']   ?? 25);
        $loP  = (float)($_POST['pct_lower']   ?? 25);
        $nuP  = (float)($_POST['pct_numbers'] ?? 25);
        $spP  = (float)($_POST['pct_special'] ?? 25);
        $pwd  = $generator->fromPercents($len, $upP, $loP, $nuP, $spP);
    } else {
        $up = max(0, (int)($_POST['count_upper']   ?? 2));
        $lo = max(0, (int)($_POST['count_lower']   ?? 3));
        $nu = max(0, (int)($_POST['count_numbers'] ?? 2));
        $sp = max(0, (int)($_POST['count_special'] ?? 2));
        $pwd = $generator->generate($up, $lo, $nu, $sp);

        // Log the generation
        $pwdRepo->logGeneration($userId, $up+$lo+$nu+$sp, $up, $lo, $nu, $sp);
    }

    header('Content-Type: application/json');
    echo json_encode(['password' => $pwd]);
    exit;
}

// Load all saved passwords
$rows = $pwdRepo->findByUser($userId);

// Decrypt each row
$entries = [];
foreach ($rows as $row) {
    try {
        $entries[] = [
            'id'       => $row['id'],
            'site'     => $row['site_name'],
            'uname'    => $row['username'],
            'password' => $enc->decrypt($row['password'], $rawKey),
            'notes'    => $row['notes'] ? $enc->decrypt($row['notes'], $rawKey) : '',
            'date'     => date('d M Y H:i', strtotime($row['created_at'])),
        ];
    } catch (\Exception $e) {
        // Corrupt record — skip silently
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Password Manager</title>
<style>
<?php include 'style.css.php'; ?>
/ Extra dashboard styles /
table { width:100%; border-collapse:collapse; margin-top:12px; font-size:14px; }
th, td { text-align:left; padding:8px 10px; border-bottom:1px solid #ddd; vertical-align:middle; }
th { background:#f4f4f4; font-weight:600; }
.pwd-cell { font-family:monospace; letter-spacing:1px; }
.toggle-btn, .copy-btn { font-size:12px; padding:3px 8px; margin-left:4px; cursor:pointer; }
.delete-btn { background:#e74c3c; color:#fff; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:12px; }
.section { background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px; margin-bottom:24px; }
.section h3 { margin:0 0 14px; font-size:16px; border-bottom:1px solid #eee; padding-bottom:8px; }
.row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.row-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.gen-output { margin-top:12px; }
.gen-output input { font-family:monospace; font-size:16px; background:#f9f9f9; font-weight:bold; }
.tab-btns { display:flex; gap:8px; margin-bottom:14px; }
.tab-btn { padding:6px 16px; border:1px solid #ccc; background:#f4f4f4; border-radius:4px; cursor:pointer; font-size:13px; }
.tab-btn.active { background:#3498db; color:#fff; border-color:#3498db; }
.tab-panel { display:none; } .tab-panel.active { display:block; }
nav { display:flex; justify-content:space-between; align-items:center;
      background:#2c3e50; color:#fff; padding:10px 20px; margin-bottom:24px; border-radius:6px; }
nav a { color:#ecf0f1; text-decoration:none; font-size:13px; }
nav a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container" style="max-width:900px">

  <nav>
    <span>Password Manager - <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
    <div>
      <a href="change_password.php">Change Password</a> &nbsp;|&nbsp;
      <a href="logout.php">Logout</a>
    </div>
  </nav>

  <?php if ($message): ?>
    <p class="<?= $msgType ?>"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <!Password Generator
  <div class="section">
    <h3>Password Generator</h3>

    <div class="tab-btns">
      <button class="tab-btn active" onclick="switchTab('count')">By Count</button>
      <button class="tab-btn" onclick="switchTab('percent')">By Percent</button>
    </div>

    <!By count>
    <div id="tab-count" class="tab-panel active">
      <div class="row-4">
        <div>
          <label>Uppercase</label>
          <input type="number" id="count_upper" value="2" min="0" max="50">
        </div>
        <div>
          <label>Lowercase</label>
          <input type="number" id="count_lower" value="3" min="0" max="50">
        </div>
        <div>
          <label>Numbers</label>
          <input type="number" id="count_numbers" value="2" min="0" max="50">
        </div>
        <div>
          <label>Special</label>
          <input type="number" id="count_special" value="2" min="0" max="50">
        </div>
      </div>
      <p style="font-size:13px;color:#666;margin-top:6px">
        Total length: <strong id="count-total">9</strong> characters
      </p>
    </div>

    <!By percent>
    <div id="tab-percent" class="tab-panel">
      <div class="row-2" style="margin-bottom:10px">
        <div>
          <label>Total length</label>
          <input type="number" id="total_length" value="12" min="4" max="128">
        </div>
      </div>
      <div class="row-4">
        <div>
          <label>Uppercase %</label>
          <input type="number" id="pct_upper" value="25" min="0" max="100">
        </div>
        <div>
          <label>Lowercase %</label>
          <input type="number" id="pct_lower" value="25" min="0" max="100">
        </div>
        <div>
          <label>Numbers %</label>
          <input type="number" id="pct_numbers" value="25" min="0" max="100">
        </div>
        <div>
          <label>Special %</label>
          <input type="number" id="pct_special" value="25" min="0" max="100">
        </div>
      </div>
    </div>

    <button onclick="generatePassword()" style="margin-top:14px">Generate Password</button>

    <div class="gen-output">
      <label>Generated Password</label>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="text" id="gen-result" readonly placeholder="Click Generate…">
        <button class="copy-btn" onclick="copyGenerated()">Copy</button>
        <button onclick="useGenerated()" style="font-size:13px;padding:7px 14px">Use</button>
      </div>
    </div>
  </div>

  <!Save New Password
  <div class="section">
    <h3>Save New Password</h3>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <div class="row-2">
        <div>
          <label>Name Surname *</label>
          <input type="text" name="site_name" id="site_name" placeholder="e.g. John Doe" required>
        </div>
        <div>
          <label>Username / Login</label>
          <input type="text" name="site_username" placeholder="example123">
        </div>
      </div>
      <div>
        <label>Password *</label>
        <input type="text" name="plain_password" id="plain_password" placeholder="Enter or paste generated password" required style="width:14.5%">
      </div>
      <div>
        <label>Notes</label>
        <input type="text" name="notes" placeholder="Optional notes">
      </div>
      <button type="submit">Save Password</button>
    </form>
  </div>

  <!Saved Passwords
  <div class="section">
    <h3>Saved Passwords (<?= count($entries) ?>)</h3>

    <?php if (empty($entries)): ?>
      <p style="color:#888">No passwords saved yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th><th>Site / Program</th><th>Username</th>
            <th>Password</th><th>Date Saved</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($entries as $i => $e): ?>
          <tr>
            <td style="color:#aaa"><?= $e['id'] ?></td>
            <td><?= htmlspecialchars($e['site']) ?></td>
            <td><?= htmlspecialchars($e['uname']) ?></td>
            <td class="pwd-cell">
              <span class="pwd-text" data-pwd="<?= htmlspecialchars($e['password']) ?>">••••••••</span>
              <button class="toggle-btn" onclick="togglePwd(this)">Show</button>
              <button class="copy-btn" onclick="copyPwd(this)">Copy</button>
            </td>
            <td style="font-size:12px;color:#888"><?= $e['date'] ?></td>
            <td style="font-size:12px;color:#666"><?= htmlspecialchars($e['notes']) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete this entry?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                <button type="submit" class="delete-btn">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<script>
//Tab switching
let activeMode = 'count';
function switchTab(mode) {
    activeMode = mode;
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + mode).classList.add('active');
    event.target.classList.add('active');
}

//pdate total label
function updateTotal() {
    const t = ['count_upper','count_lower','count_numbers','count_special']
              .reduce((s,id) => s + (parseInt(document.getElementById(id)?.value)||0), 0);
    document.getElementById('count-total').textContent = t;
}
['count_upper','count_lower','count_numbers','count_special']
    .forEach(id => document.getElementById(id)?.addEventListener('input', updateTotal));

//Generate
function generatePassword() {
    const body = new FormData();
    body.append('action', 'generate');
    body.append('mode', activeMode);

    if (activeMode === 'count') {
        ['count_upper','count_lower','count_numbers','count_special']
            .forEach(id => body.append(id, document.getElementById(id).value));
    } else {
        ['total_length','pct_upper','pct_lower','pct_numbers','pct_special']
            .forEach(id => body.append(id, document.getElementById(id).value));
    }

    fetch('dashboard.php', { method:'POST', body })
        .then(r => r.json())
        .then(d => { document.getElementById('gen-result').value = d.password; });
}

function copyGenerated() {
    const v = document.getElementById('gen-result').value;
    if (v) navigator.clipboard.writeText(v);
}

function useGenerated() {
    const v = document.getElementById('gen-result').value;
    if (v) document.getElementById('plain_password').value = v;
}

//Password reveal / copy in table
function togglePwd(btn) {
    const span = btn.previousElementSibling;
    if (span.textContent === '••••••••') {
        span.textContent = span.dataset.pwd;
        btn.textContent  = 'Hide';
    } else {
        span.textContent = '••••••••';
        btn.textContent  = 'Show';
    }
}

function copyPwd(btn) {
    const span = btn.previousElementSibling.previousElementSibling;
    navigator.clipboard.writeText(span.dataset.pwd);
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 1500);
}
</script>
</body>
</html>
