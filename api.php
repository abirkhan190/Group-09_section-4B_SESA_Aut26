<?php

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campuscareerbd');
define('ADMIN_EMAIL', 'admin@campuscareer.bd');
define('ADMIN_PASSWORD', 'Admin@123');

ini_set('display_errors', '0');   // never print PHP warnings into the JSON reply
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cut($v, $n) {
    $v = (string)$v;
    return function_exists('mb_substr') ? mb_substr($v, 0, $n, 'UTF-8') : substr($v, 0, $n);
}

function db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $opts = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );
    $boot = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    $boot->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    create_tables($pdo);
    return $pdo;
}

function create_tables($pdo) {
    $tail = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $pdo->exec('CREATE TABLE IF NOT EXISTS students (
        seq INT NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        name VARCHAR(190) NOT NULL DEFAULT \'\',
        university VARCHAR(190) NOT NULL DEFAULT \'\',
        password_hash VARCHAR(255) NOT NULL DEFAULT \'\',
        verified TINYINT(1) NOT NULL DEFAULT 0,
        data LONGTEXT NOT NULL,
        PRIMARY KEY (email),
        UNIQUE KEY seq_idx (seq)
    )' . $tail);

    $pdo->exec('CREATE TABLE IF NOT EXISTS employers (
        seq INT NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        company_name VARCHAR(190) NOT NULL DEFAULT \'\',
        industry VARCHAR(190) NOT NULL DEFAULT \'\',
        password_hash VARCHAR(255) NOT NULL DEFAULT \'\',
        verified TINYINT(1) NOT NULL DEFAULT 0,
        data LONGTEXT NOT NULL,
        PRIMARY KEY (email),
        UNIQUE KEY seq_idx (seq)
    )' . $tail);

    $pdo->exec('CREATE TABLE IF NOT EXISTS jobs (
        seq INT NOT NULL AUTO_INCREMENT,
        id INT NOT NULL,
        title VARCHAR(190) NOT NULL DEFAULT \'\',
        company VARCHAR(190) NOT NULL DEFAULT \'\',
        location VARCHAR(190) NOT NULL DEFAULT \'\',
        job_type VARCHAR(60) NOT NULL DEFAULT \'\',
        status VARCHAR(20) NOT NULL DEFAULT \'Pending\',
        posted_by VARCHAR(190) NOT NULL DEFAULT \'\',
        data LONGTEXT NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY seq_idx (seq)
    )' . $tail);

    $pdo->exec('CREATE TABLE IF NOT EXISTS applications (
        seq INT NOT NULL AUTO_INCREMENT,
        student_email VARCHAR(190) NOT NULL,
        job_id INT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT \'Applied\',
        applied_at VARCHAR(40) NOT NULL DEFAULT \'\',
        data LONGTEXT NOT NULL,
        PRIMARY KEY (student_email, job_id),
        UNIQUE KEY seq_idx (seq)
    )' . $tail);

    $pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
        seq INT NOT NULL AUTO_INCREMENT,
        id VARCHAR(64) NOT NULL,
        to_email VARCHAR(190) NOT NULL DEFAULT \'\',
        message TEXT NOT NULL,
        created_at VARCHAR(40) NOT NULL DEFAULT \'\',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        data LONGTEXT NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY seq_idx (seq)
    )' . $tail);

    $pdo->exec('CREATE TABLE IF NOT EXISTS meta (
        k VARCHAR(64) NOT NULL,
        v TEXT NOT NULL,
        PRIMARY KEY (k)
    )' . $tail);
}

// ---------- reading ----------

function read_list($pdo, $table, $blankPassword) {
    $list = array();
    foreach ($pdo->query('SELECT data FROM ' . $table . ' ORDER BY seq') as $row) {
        $o = json_decode($row['data'], true);
        if (!is_array($o)) {
            continue;
        }
        if ($blankPassword) {
            $o['password'] = '';   // never send password hashes to the browser
        }
        $list[] = $o;
    }
    return $list;
}

function load_all() {
    $pdo = db();
    $meta = array();
    foreach ($pdo->query('SELECT k, v FROM meta') as $row) {
        $meta[$row['k']] = $row['v'];
    }
    return array(
        'students'      => read_list($pdo, 'students', true),
        'employers'     => read_list($pdo, 'employers', true),
        'jobsList'      => read_list($pdo, 'jobs', false),
        'applications'  => read_list($pdo, 'applications', false),
        'notifications' => read_list($pdo, 'notifications', false),
        'meta'          => (object)$meta
    );
}

// ---------- writing (replace a whole table inside a transaction) ----------

function make_hash($incoming, $oldHash) {
    $incoming = (string)$incoming;
    if ($incoming === '') {
        return $oldHash;                       // keep the existing password
    }
    if (strpos($incoming, '$2y$') === 0) {
        return $incoming;                      // already hashed
    }
    return password_hash($incoming, PASSWORD_DEFAULT);
}

function save_collection($name, $items) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($name === 'students' || $name === 'employers') {
            $old = array();
            foreach ($pdo->query('SELECT email, password_hash FROM ' . $name) as $r) {
                $old[$r['email']] = $r['password_hash'];
            }
            $pdo->exec('DELETE FROM ' . $name);
            if ($name === 'students') {
                $ins = $pdo->prepare('INSERT INTO students (email, name, university, password_hash, verified, data) VALUES (?,?,?,?,?,?)');
            } else {
                $ins = $pdo->prepare('INSERT INTO employers (email, company_name, industry, password_hash, verified, data) VALUES (?,?,?,?,?,?)');
            }
            $seen = array();
            foreach ($items as $it) {
                if (!is_array($it) || empty($it['email'])) {
                    continue;
                }
                $email = cut(trim($it['email']), 190);
                if (isset($seen[$email])) {
                    continue;
                }
                $seen[$email] = true;
                $pw = isset($it['password']) ? $it['password'] : '';
                unset($it['password']);
                $hash = make_hash($pw, isset($old[$email]) ? $old[$email] : '');
                $json = json_encode($it, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    continue;
                }
                $verified = !empty($it['verified']) ? 1 : 0;
                if ($name === 'students') {
                    $ins->execute(array($email, cut(isset($it['name']) ? $it['name'] : '', 190), cut(isset($it['university']) ? $it['university'] : '', 190), $hash, $verified, $json));
                } else {
                    $ins->execute(array($email, cut(isset($it['companyName']) ? $it['companyName'] : '', 190), cut(isset($it['industry']) ? $it['industry'] : '', 190), $hash, $verified, $json));
                }
            }
        } elseif ($name === 'jobsList') {
            $pdo->exec('DELETE FROM jobs');
            $ins = $pdo->prepare('INSERT INTO jobs (id, title, company, location, job_type, status, posted_by, data) VALUES (?,?,?,?,?,?,?,?)');
            $seen = array();
            foreach ($items as $it) {
                if (!is_array($it) || !isset($it['id'])) {
                    continue;
                }
                $id = (int)$it['id'];
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $json = json_encode($it, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    continue;
                }
                $ins->execute(array(
                    $id,
                    cut(isset($it['title']) ? $it['title'] : '', 190),
                    cut(isset($it['company']) ? $it['company'] : '', 190),
                    cut(isset($it['location']) ? $it['location'] : '', 190),
                    cut(isset($it['type']) ? $it['type'] : '', 60),
                    cut(isset($it['status']) ? $it['status'] : 'Pending', 20),
                    cut(isset($it['postedBy']) ? $it['postedBy'] : '', 190),
                    $json
                ));
            }
        } elseif ($name === 'applications') {
            $pdo->exec('DELETE FROM applications');
            $ins = $pdo->prepare('INSERT INTO applications (student_email, job_id, status, applied_at, data) VALUES (?,?,?,?,?)');
            $seen = array();
            foreach ($items as $it) {
                if (!is_array($it) || empty($it['studentEmail']) || !isset($it['jobId'])) {
                    continue;
                }
                $key = $it['studentEmail'] . '|' . (int)$it['jobId'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $json = json_encode($it, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    continue;
                }
                $ins->execute(array(
                    cut($it['studentEmail'], 190),
                    (int)$it['jobId'],
                    cut(isset($it['status']) ? $it['status'] : 'Applied', 30),
                    cut(isset($it['appliedAt']) ? $it['appliedAt'] : '', 40),
                    $json
                ));
            }
        } elseif ($name === 'notifications') {
            $pdo->exec('DELETE FROM notifications');
            $ins = $pdo->prepare('INSERT INTO notifications (id, to_email, message, created_at, is_read, data) VALUES (?,?,?,?,?,?)');
            $seen = array();
            foreach ($items as $it) {
                if (!is_array($it) || empty($it['id'])) {
                    continue;
                }
                $id = cut($it['id'], 64);
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $json = json_encode($it, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    continue;
                }
                $ins->execute(array(
                    $id,
                    cut(isset($it['to']) ? $it['to'] : '', 190),
                    (string)(isset($it['message']) ? $it['message'] : ''),
                    cut(isset($it['createdAt']) ? $it['createdAt'] : '', 40),
                    !empty($it['read']) ? 1 : 0,
                    $json
                ));
            }
        } else {
            throw new Exception('Unknown collection');
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// ---------- login ----------

function do_login($role, $email, $password) {
    $email = strtolower(trim((string)$email));
    $password = (string)$password;
    if ($role === 'admin') {
        return $email === ADMIN_EMAIL && hash_equals(ADMIN_PASSWORD, $password);
    }
    if ($password === '') {
        return false;
    }
    if ($role === 'student') {
        $table = 'students';
    } elseif ($role === 'employer') {
        $table = 'employers';
    } else {
        return false;
    }
    $st = db()->prepare('SELECT password_hash FROM ' . $table . ' WHERE email = ?');
    $st->execute(array($email));
    $row = $st->fetch();
    if (!$row || $row['password_hash'] === '') {
        return false;
    }
    return password_verify($password, $row['password_hash']);
}

// ---------- router ----------

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $raw = file_get_contents('php://input');
    $body = ($raw !== '' && $raw !== false) ? json_decode($raw, true) : null;

    if ($action === 'load') {
        out(load_all());
    }

    if ($action === 'save') {
        $name = isset($_GET['name']) ? $_GET['name'] : '';
        $allowed = array('students', 'employers', 'jobsList', 'applications', 'notifications');
        if (!in_array($name, $allowed, true) || !is_array($body)) {
            out(array('ok' => false, 'error' => 'Bad request'), 400);
        }
        save_collection($name, $body);
        out(array('ok' => true));
    }

    if ($action === 'meta') {
        $key = isset($_GET['key']) ? cut($_GET['key'], 64) : '';
        if ($key === '' || !is_array($body) || !isset($body['value'])) {
            out(array('ok' => false, 'error' => 'Bad request'), 400);
        }
        $st = db()->prepare('INSERT INTO meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
        $st->execute(array($key, (string)$body['value']));
        out(array('ok' => true));
    }

    if ($action === 'login') {
        if (!is_array($body)) {
            out(array('ok' => false), 400);
        }
        $ok = do_login(
            isset($body['role']) ? $body['role'] : '',
            isset($body['email']) ? $body['email'] : '',
            isset($body['password']) ? $body['password'] : ''
        );
        out(array('ok' => $ok));
    }

    out(array('error' => 'Unknown action'), 400);
} catch (Exception $e) {
    out(array('ok' => false, 'error' => 'Server error: ' . $e->getMessage()), 500);
}
