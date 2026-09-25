<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/security.php';

function ensureFruitNinjaScoresTable(PDO $pdo): void
{
  $pdo->exec(
    'CREATE TABLE IF NOT EXISTS fruit_ninja_scores (
      account_id INT NOT NULL PRIMARY KEY,
      best_score INT NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
      INDEX (best_score)
    )'
  );
}

if (!isset($_SESSION['fn_highscore'])) {
  $_SESSION['fn_highscore'] = 0;
}

if (isset($_GET['action']) && in_array($_GET['action'], ['save_score', 'leaderboard'], true)) {
  header('Content-Type: application/json');
  require_once __DIR__ . '/includes/general/db.php';
  ensureFruitNinjaScoresTable($pdo);

  if ($_GET['action'] === 'leaderboard') {
    $rows = $pdo->query(
      'SELECT CONCAT(firstname, " ", LEFT(lastname, 1), ".") AS player, s.best_score
       FROM fruit_ninja_scores AS s
       JOIN account AS a ON a.id = s.account_id
       ORDER BY s.best_score DESC, s.updated_at ASC, s.account_id ASC
       LIMIT 10'
    )->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['scores' => $rows]);
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
  }
  jcm_require_csrf();

  $score = max(0, min(1000000, (int) ($_POST['score'] ?? 0)));
  $_SESSION['fn_highscore'] = max((int) $_SESSION['fn_highscore'], $score);
  $accountId = (int) ($_SESSION['id'] ?? 0);
  if ($accountId <= 0) {
    echo json_encode(['requires_login' => true, 'highscore' => $_SESSION['fn_highscore']]);
    exit;
  }
  if ($score === 0) {
    echo json_encode(['highscore' => (int) $_SESSION['fn_highscore'], 'is_new_record' => false]);
    exit;
  }

  $previousStmt = $pdo->prepare('SELECT best_score FROM fruit_ninja_scores WHERE account_id = ?');
  $previousStmt->execute([$accountId]);
  $previousScore = $previousStmt->fetchColumn();
  $isNewRecord = $score > (int) ($previousScore === false ? 0 : $previousScore);
  $saveStmt = $pdo->prepare(
    'INSERT INTO fruit_ninja_scores (account_id, best_score) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE
       updated_at = IF(VALUES(best_score) > best_score, CURRENT_TIMESTAMP, updated_at),
       best_score = GREATEST(best_score, VALUES(best_score))'
  );
  $saveStmt->execute([$accountId, $score]);
  $bestScore = max((int) ($previousScore === false ? 0 : $previousScore), $score);
  echo json_encode(['highscore' => $bestScore, 'is_new_record' => $isNewRecord]);
  exit;
}

$initialHighscore = (int) $_SESSION['fn_highscore'];
$isLoggedIn = !empty($_SESSION['id']);
if ($isLoggedIn) {
  require_once __DIR__ . '/includes/general/db.php';
  ensureFruitNinjaScoresTable($pdo);
  $personalBestStmt = $pdo->prepare('SELECT best_score FROM fruit_ninja_scores WHERE account_id = ?');
  $personalBestStmt->execute([(int) $_SESSION['id']]);
  $personalBest = $personalBestStmt->fetchColumn();
  $initialHighscore = max($initialHighscore, (int) ($personalBest === false ? 0 : $personalBest));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Fruit Ninja PHP</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
      user-select: none;
      -webkit-user-select: none;
    }

    html,
    body {
      width: 100%;
      height: 100%;
      overflow: hidden;
      background: #05050a;
      font-family: 'Segoe UI', Tahoma, sans-serif;
      touch-action: none;
      overscroll-behavior: none;
    }

    body {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    #gameContainer {
      position: relative;
      width: 100%;
      max-width: 560px;
      height: 100vh;
      height: 100dvh;
      max-height: 1000px;
      overflow: hidden;
      margin: 0 auto;
    }

    /* Sur les écrans larges (tablette/desktop), on borde la zone de jeu
       pour éviter qu'elle ne s'étire de façon disproportionnée */
    @media (min-width: 620px) {
      #gameContainer {
        border-radius: 18px;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.06), 0 30px 80px rgba(0, 0, 0, 0.7);
      }
    }

    canvas {
      display: block;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, #1a1a2e 0%, #16213e 60%, #0f0f1a 100%);
    }

    #hud {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: max(32px, calc(env(safe-area-inset-top, 0px) + 14px)) clamp(14px, 4vw, 24px) 0;
      pointer-events: none;
      z-index: 10;
    }

    #leaderboardToggle {
      position: absolute;
      top: max(78px, calc(env(safe-area-inset-top, 0px) + 62px));
      right: clamp(14px, 4vw, 24px);
      z-index: 12;
      width: 44px;
      height: 44px;
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 50%;
      background: rgba(10, 14, 30, 0.82);
      color: #ffd166;
      font-size: 20px;
      cursor: pointer;
    }

    #score {
      font-size: clamp(24px, 7vw, 36px);
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
    }

    #hiscoreBox {
      text-align: right;
      color: #ffd166;
      font-size: clamp(11px, 3vw, 15px);
      font-weight: 600;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
    }

    #lives {
      position: absolute;
      top: max(60px, calc(env(safe-area-inset-top) + 50px));
      left: clamp(14px, 4vw, 24px);
      display: flex;
      gap: 6px;
      z-index: 10;
      pointer-events: none;
    }

    .life {
      font-size: clamp(18px, 5vw, 24px);
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.6));
    }

    .life.lost {
      opacity: 0.25;
      filter: grayscale(1);
    }

    #combo {
      position: absolute;
      top: 40%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.5);
      font-size: clamp(26px, 8vw, 44px);
      font-weight: 900;
      color: #ff6b35;
      text-shadow: 0 0 20px rgba(255, 107, 53, 0.8), 0 2px 6px rgba(0, 0, 0, 0.6);
      opacity: 0;
      pointer-events: none;
      z-index: 20;
      transition: none;
    }

    .overlay {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 40%, rgba(30, 20, 50, 0.92), rgba(5, 5, 12, 0.97));
      z-index: 30;
      text-align: center;
      padding: max(16px, env(safe-area-inset-top, 0px)) clamp(16px, 6vw, 32px) max(16px, env(safe-area-inset-bottom, 0px));
      overflow-y: auto;
    }

    .hidden {
      display: none !important;
    }

    .title {
      font-size: clamp(32px, 10vw, 50px);
      font-weight: 900;
      background: linear-gradient(135deg, #ff6b35, #ffd166);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 6px;
      letter-spacing: 1px;
    }

    .subtitle {
      color: #9aa0b4;
      font-size: clamp(13px, 4vw, 16px);
      margin-bottom: 30px;
      line-height: 1.4;
    }

    .bigEmoji {
      font-size: clamp(46px, 15vw, 68px);
      margin-bottom: 16px;
      filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.5));
    }

    .btn {
      background: linear-gradient(135deg, #ff6b35, #f7931e);
      color: #fff;
      border: none;
      padding: clamp(13px, 3.5vw, 16px) clamp(32px, 10vw, 46px);
      font-size: clamp(16px, 4.5vw, 19px);
      font-weight: 800;
      border-radius: 50px;
      box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
      cursor: pointer;
      letter-spacing: 0.5px;
      margin-top: 6px;
      -webkit-tap-highlight-color: transparent;
    }

    .btn:active {
      transform: scale(0.95);
    }

    .statLine {
      color: #e8e8f0;
      font-size: clamp(15px, 4vw, 17px);
      margin: 3px 0;
    }

    .statValue {
      color: #ffd166;
      font-weight: 800;
    }

    .newRecord {
      color: #4ade80;
      font-weight: 800;
      font-size: clamp(13px, 3.5vw, 15px);
      margin-top: 10px;
      text-shadow: 0 0 12px rgba(74, 222, 128, 0.6);
    }

    #finalStats {
      margin: 18px 0 22px 0;
    }

    #startBombWarning {
      color: #ff6b6b;
      font-size: clamp(11px, 3vw, 13px);
      margin-top: 18px;
      opacity: 0.8;
    }

    .leaderboard-panel {
      width: min(100%, 390px);
      max-height: 100%;
      overflow-y: auto;
      color: #fff;
    }

    .leaderboard-panel h2 {
      color: #ffd166;
      font-size: 26px;
      margin-bottom: 18px;
    }

    #leaderboardList {
      list-style: none;
      text-align: left;
      width: 100%;
      margin-bottom: 18px;
    }

    #leaderboardList li {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      padding: 10px 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
      font-size: 14px;
    }

    #leaderboardList .leaderboard-empty {
      justify-content: center;
      color: #9aa0b4;
    }

    .secondaryBtn {
      border: 1px solid rgba(255, 255, 255, 0.4);
      background: transparent;
      color: #fff;
      box-shadow: none;
    }

    .savePromptText {
      color: #e8e8f0;
      max-width: 320px;
      line-height: 1.5;
      margin-bottom: 18px;
    }
  </style>
</head>

<body>

  <div id="gameContainer">
    <canvas id="gameCanvas"></canvas>

    <div id="hud">
      <div id="score">0</div>
      <div id="hiscoreBox">MEILLEUR SCORE<br><span id="hiscoreValue"><?php echo $initialHighscore; ?></span></div>
    </div>

    <button id="leaderboardToggle" type="button" aria-label="Voir le classement" title="Classement">🏆</button>

    <div id="lives">
      <span class="life">❤️</span>
      <span class="life">❤️</span>
      <span class="life">❤️</span>
    </div>

    <div id="combo">COMBO x3!</div>

    <!-- Écran de démarrage -->
    <div id="startScreen" class="overlay">
      <div class="bigEmoji">🍉🔪</div>
      <div class="title">FRUIT NINJA</div>
      <div class="subtitle">Glisse ton doigt pour trancher les fruits.<br>Évite les bombes !</div>
      <button class="btn" id="startBtn">JOUER</button>
      <button class="btn secondaryBtn" id="startLeaderboardBtn" type="button">Classement global</button>
      <div id="startBombWarning">💣 3 fruits ratés ou 1 bombe touchée = perdu</div>
    </div>

    <!-- Écran game over -->
    <div id="gameOverScreen" class="overlay hidden">
      <div class="bigEmoji" id="gameOverEmoji">💥</div>
      <div class="title" style="font-size:clamp(26px, 8vw, 34px);">GAME OVER</div>
      <div id="finalStats">
        <div class="statLine">Score final : <span class="statValue" id="finalScore">0</span></div>
        <div class="statLine">Meilleur combo : <span class="statValue" id="finalCombo">x0</span></div>
        <div id="recordMsg" class="newRecord hidden">🏆 NOUVEAU RECORD !</div>
      </div>
      <button class="btn" id="restartBtn">REJOUER</button>
    </div>

    <div id="leaderboardScreen" class="overlay hidden" role="dialog" aria-modal="true" aria-labelledby="leaderboardTitle">
      <div class="leaderboard-panel">
        <h2 id="leaderboardTitle">Classement global</h2>
        <ol id="leaderboardList"><li class="leaderboard-empty">Chargement...</li></ol>
        <button class="btn secondaryBtn" id="closeLeaderboard" type="button">Fermer</button>
      </div>
    </div>

    <div id="savePrompt" class="overlay hidden" role="dialog" aria-modal="true" aria-labelledby="savePromptTitle">
      <div class="bigEmoji">🏆</div>
      <div class="title" id="savePromptTitle" style="font-size:clamp(26px, 8vw, 34px);">Sauvegarder mon score</div>
      <p class="savePromptText">Connecte-toi pour enregistrer ton score dans le classement global.</p>
      <a class="btn" id="loginToSave" href="login.php?return_to=fruit_ninja.php">Se connecter</a>
      <button class="btn secondaryBtn" id="dismissSavePrompt" type="button">Plus tard</button>
    </div>
  </div>

  <script>
    (function () {
      'use strict';

      const canvas = document.getElementById('gameCanvas');
      const ctx = canvas.getContext('2d');
      const container = document.getElementById('gameContainer');
      const IS_LOGGED_IN = <?php echo json_encode($isLoggedIn); ?>;
      const CSRF_TOKEN = <?php echo json_encode(jcm_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

      let W, H, DPR;
      // Facteur d'échelle pour la taille des fruits/bombes, calculé à partir
      // de la largeur réelle du conteneur de jeu (référence : 390px, un
      // iPhone standard). Ainsi les fruits gardent une taille cohérente
      // qu'on joue sur un petit téléphone, une tablette ou un desktop.
      let sizeScale = 1;

      function resize() {
        DPR = Math.min(window.devicePixelRatio || 1, 2);
        W = container.clientWidth;
        H = container.clientHeight;
        canvas.width = W * DPR;
        canvas.height = H * DPR;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);

        sizeScale = W / 390;
        sizeScale = Math.max(0.72, Math.min(sizeScale, 1.35));
      }
      window.addEventListener('resize', resize);
      // Sur mobile, les dimensions ne sont pas toujours immédiatement
      // correctes juste après un changement d'orientation.
      window.addEventListener('orientationchange', function () {
        setTimeout(resize, 50);
        setTimeout(resize, 300);
      });
      resize();

      // ---------- État du jeu ----------
      const FRUIT_TYPES = [
        { emoji: '🍉', radius: 42, points: 1, color: '#ff4d6d' },
        { emoji: '🍊', radius: 32, points: 1, color: '#ff9f1c' },
        { emoji: '🍎', radius: 30, points: 1, color: '#e63946' },
        { emoji: '🍋', radius: 28, points: 1, color: '#ffea00' },
        { emoji: '🍇', radius: 30, points: 2, color: '#7b2cbf' },
        { emoji: '🍓', radius: 26, points: 2, color: '#ff3366' },
        { emoji: '🥝', radius: 26, points: 2, color: '#a3d900' },
        { emoji: '🍍', radius: 34, points: 3, color: '#ffd60a' }
      ];
      const BOMB = { emoji: '💣', radius: 34 };

      let score = 0;
      let highscore = <?php echo $initialHighscore; ?>;
      let lives = 3;
      let running = false;
      let objects = [];       // fruits & bombes en vol
      let particles = [];     // éclats de jus
      let slashPoints = [];   // trace de la lame
      let spawnTimer = 0;
      let spawnInterval = 1100;
      let elapsedGame = 0;
      let comboCount = 0;
      let comboTimer = 0;
      let bestCombo = 0;
      let lastFrameTime = 0;
      let shakeTime = 0;
      let animationId = null;

      const scoreEl = document.getElementById('score');
      const hiscoreValueEl = document.getElementById('hiscoreValue');
      const livesEls = document.querySelectorAll('.life');
      const comboEl = document.getElementById('combo');
      const startScreen = document.getElementById('startScreen');
      const gameOverScreen = document.getElementById('gameOverScreen');
      const finalScoreEl = document.getElementById('finalScore');
      const finalComboEl = document.getElementById('finalCombo');
      const recordMsgEl = document.getElementById('recordMsg');
      const gameOverEmojiEl = document.getElementById('gameOverEmoji');

      function rand(min, max) { return Math.random() * (max - min) + min; }

      // ---------- Objets volants (fruits/bombes) ----------
      function spawnWave() {
        const count = Math.random() < 0.25 ? 2 : (Math.random() < 0.08 ? 3 : 1);
        for (let i = 0; i < count; i++) {
          setTimeout(() => spawnOne(), i * 140);
        }
      }

      function spawnOne() {
        if (!running) return;
        const isBomb = Math.random() < 0.12;
        let def;
        if (isBomb) {
          def = BOMB;
        } else {
          def = FRUIT_TYPES[Math.floor(Math.random() * FRUIT_TYPES.length)];
        }
        const radius = def.radius * sizeScale;
        const horizontalMargin = Math.min(radius + 8, W / 4);
        const startX = rand(horizontalMargin, W - horizontalMargin);
        const targetHeightFactor = rand(0.35, 0.62); // hauteur atteinte relative
        // Gravité plus douce pour garder les fruits visibles assez longtemps
        // sans qu’ils quittent l’écran trop rapidement.
        const gravity = 0.0000009 * H;
        // Les objets démarrent dans la zone visible, même sur les petits écrans.
        const startY = Math.max(radius + 8, H - radius - 8);
        const apexY = H * (1 - targetHeightFactor) - H * 0.05;
        const dist = Math.max(1, startY - apexY);
        const vy = -Math.sqrt(2 * gravity * dist);
        const vx = rand(-0.22, 0.22) * (W / 700);

        objects.push({
          x: startX,
          y: startY,
          vx: vx,
          vy: vy,
          gravity: gravity,
          radius: radius,
          emoji: def.emoji,
          points: def.points || 0,
          isBomb: isBomb,
          sliced: false,
          rotation: rand(0, Math.PI * 2),
          rotSpeed: rand(-0.04, 0.04),
          halves: null,
          missed: false
        });
      }

      function createSliceParticles(obj, cutAngle) {
        const n = 10;
        for (let i = 0; i < n; i++) {
          const angle = rand(0, Math.PI * 2);
          const speed = rand(1.5, 5);
          particles.push({
            x: obj.x, y: obj.y,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed - 1,
            gravity: obj.gravity,
            life: 1,
            decay: rand(0.015, 0.03),
            size: rand(3, 7) * sizeScale,
            color: obj.isBomb ? '#555' : (FRUIT_TYPES.find(f => f.emoji === obj.emoji) || {}).color || '#fff'
          });
        }
        // deux moitiés du fruit qui volent
        obj.halves = [
          { x: obj.x, y: obj.y, vx: obj.vx - 2.5, vy: obj.vy - 1, rot: obj.rotation, rotSpeed: -0.15, offset: -1 },
          { x: obj.x, y: obj.y, vx: obj.vx + 2.5, vy: obj.vy - 1, rot: obj.rotation, rotSpeed: 0.15, offset: 1 }
        ];
      }

      // ---------- Slash / interaction ----------
      let pointerActive = false;

      function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches && e.touches.length) {
          return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
      }

      function onPointerDown(e) {
        e.preventDefault();
        pointerActive = true;
        const p = getPos(e);
        slashPoints = [{ x: p.x, y: p.y, t: performance.now() }];
      }

      function onPointerMove(e) {
        if (!pointerActive || !running) return;
        e.preventDefault();
        const p = getPos(e);
        const now = performance.now();
        slashPoints.push({ x: p.x, y: p.y, t: now });
        if (slashPoints.length > 14) slashPoints.shift();

        if (slashPoints.length >= 2) {
          const a = slashPoints[slashPoints.length - 2];
          const b = slashPoints[slashPoints.length - 1];
          checkSliceAlongSegment(a, b);
        }
      }

      function onPointerUp(e) {
        pointerActive = false;
        slashPoints = [];
      }

      canvas.addEventListener('mousedown', onPointerDown);
      canvas.addEventListener('mousemove', onPointerMove);
      window.addEventListener('mouseup', onPointerUp);
      canvas.addEventListener('touchstart', onPointerDown, { passive: false });
      canvas.addEventListener('touchmove', onPointerMove, { passive: false });
      canvas.addEventListener('touchend', onPointerUp, { passive: false });
      canvas.addEventListener('touchcancel', onPointerUp, { passive: false });

      function distToSegment(px, py, ax, ay, bx, by) {
        const dx = bx - ax, dy = by - ay;
        const lenSq = dx * dx + dy * dy;
        let t = lenSq === 0 ? 0 : ((px - ax) * dx + (py - ay) * dy) / lenSq;
        t = Math.max(0, Math.min(1, t));
        const cx = ax + t * dx, cy = ay + t * dy;
        return Math.hypot(px - cx, py - cy);
      }

      function checkSliceAlongSegment(a, b) {
        for (const obj of objects) {
          if (obj.sliced) continue;
          const d = distToSegment(obj.x, obj.y, a.x, a.y, b.x, b.y);
          if (d < obj.radius) {
            sliceObject(obj);
          }
        }
      }

      function sliceObject(obj) {
        obj.sliced = true;
        createSliceParticles(obj);

        if (obj.isBomb) {
          triggerBombHit();
          return;
        }

        score += obj.points;
        scoreEl.textContent = score;

        comboCount++;
        comboTimer = 650; // ms fenêtre de combo
        if (comboCount >= 2) {
          showCombo(comboCount);
        }
        if (comboCount > bestCombo) bestCombo = comboCount;
      }

      function showCombo(n) {
        comboEl.textContent = 'COMBO x' + n + '!';
        comboEl.style.transition = 'none';
        comboEl.style.opacity = '1';
        comboEl.style.transform = 'translate(-50%, -50%) scale(1.15)';
        requestAnimationFrame(() => {
          comboEl.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          setTimeout(() => {
            comboEl.style.opacity = '0';
            comboEl.style.transform = 'translate(-50%, -50%) scale(0.6)';
          }, 260);
        });
      }

      function triggerBombHit() {
        shakeTime = 300;
        endGame(true);
      }

      function loseLife() {
        lives--;
        const idx = 3 - lives - 1;
        if (livesEls[idx]) livesEls[idx].classList.add('lost');
        shakeTime = Math.max(shakeTime, 140);
        if (lives <= 0) {
          endGame(false);
        }
      }

      // ---------- Boucle de jeu ----------
      function update(dt) {
        elapsedGame += dt;

        // difficulté progressive
        spawnInterval = Math.max(480, 1100 - elapsedGame / 90);

        spawnTimer += dt;
        if (spawnTimer > spawnInterval) {
          spawnTimer = 0;
          spawnWave();
        }

        if (comboTimer > 0) {
          comboTimer -= dt;
          if (comboTimer <= 0) comboCount = 0;
        }

        for (let i = objects.length - 1; i >= 0; i--) {
          const obj = objects[i];

          if (!obj.sliced) {
            obj.vy += obj.gravity * dt;
            obj.x += obj.vx * dt;
            obj.y += obj.vy * dt;
            obj.rotation += obj.rotSpeed * dt;

            if (obj.y - obj.radius > H + 20) {
              objects.splice(i, 1);
              if (!obj.isBomb && !obj.missed) {
                obj.missed = true;
                loseLife();
              }
            }
          } else {
            // moitiés qui tombent
            if (obj.halves) {
              for (const h of obj.halves) {
                h.vy += obj.gravity * dt;
                h.x += h.vx * dt;
                h.y += h.vy * dt;
                h.rot += h.rotSpeed * dt;
              }
            }
            obj._fadeTimer = (obj._fadeTimer || 0) + dt;
            if (obj._fadeTimer > 600 || obj.y > H + 100) {
              objects.splice(i, 1);
            }
          }
        }

        for (let i = particles.length - 1; i >= 0; i--) {
          const p = particles[i];
          p.vy += p.gravity * dt;
          p.x += p.vx * dt;
          p.y += p.vy * dt;
          p.life -= p.decay * dt * 0.06;
          if (p.life <= 0) particles.splice(i, 1);
        }

        if (shakeTime > 0) shakeTime -= dt;
      }

      function drawBackground() {
        ctx.clearRect(0, 0, W, H);
      }

      function drawSlash() {
        if (slashPoints.length < 2) return;
        ctx.save();
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        for (let i = 1; i < slashPoints.length; i++) {
          const p0 = slashPoints[i - 1];
          const p1 = slashPoints[i];
          const alpha = i / slashPoints.length;
          ctx.strokeStyle = `rgba(255,255,255,${alpha * 0.9})`;
          ctx.lineWidth = 3 + alpha * 6;
          ctx.shadowColor = 'rgba(180,220,255,0.9)';
          ctx.shadowBlur = 12;
          ctx.beginPath();
          ctx.moveTo(p0.x, p0.y);
          ctx.lineTo(p1.x, p1.y);
          ctx.stroke();
        }
        ctx.restore();
      }

      function drawObjects() {
        for (const obj of objects) {
          ctx.save();
          if (!obj.sliced) {
            ctx.translate(obj.x, obj.y);
            ctx.rotate(obj.rotation);
            ctx.font = (obj.radius * 1.7) + 'px "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = 'rgba(0,0,0,0.35)';
            ctx.shadowBlur = 8;
            ctx.fillText(obj.emoji, 0, 0);
          } else if (obj.halves && !obj.isBomb) {
            const alpha = Math.max(0, 1 - (obj._fadeTimer || 0) / 600);
            ctx.globalAlpha = alpha;
            for (const h of obj.halves) {
              ctx.save();
              ctx.translate(h.x, h.y);
              ctx.rotate(h.rot);
              ctx.font = (obj.radius * 1.7) + 'px "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              // on simule une demi-forme via un clip
              ctx.beginPath();
              ctx.rect(h.offset < 0 ? -obj.radius : 0, -obj.radius, obj.radius, obj.radius * 2);
              ctx.clip();
              ctx.fillText(obj.emoji, 0, 0);
              ctx.restore();
            }
          }
          ctx.restore();
        }

        for (const p of particles) {
          ctx.save();
          ctx.globalAlpha = Math.max(0, p.life);
          ctx.fillStyle = p.color;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
          ctx.fill();
          ctx.restore();
        }
      }

      function render() {
        ctx.save();
        if (shakeTime > 0) {
          const mag = 6 * (shakeTime / 300);
          ctx.translate(rand(-mag, mag), rand(-mag, mag));
        }
        drawBackground();
        drawObjects();
        drawSlash();
        ctx.restore();
      }

      function loop(ts) {
        if (!running) return;
        if (!lastFrameTime) lastFrameTime = ts;
        const dt = Math.min(32, ts - lastFrameTime);
        lastFrameTime = ts;

        update(dt);
        render();

        if (running) {
          animationId = requestAnimationFrame(loop);
        }
      }

      // ---------- Gestion des écrans ----------
      function resetGameState() {
        score = 0;
        lives = 3;
        objects = [];
        particles = [];
        slashPoints = [];
        spawnTimer = 0;
        spawnInterval = 1100;
        elapsedGame = 0;
        comboCount = 0;
        comboTimer = 0;
        bestCombo = 0;
        lastFrameTime = 0;
        shakeTime = 0;
        scoreEl.textContent = '0';
        livesEls.forEach(el => el.classList.remove('lost'));
        comboEl.style.opacity = '0';
      }

      function startGame() {
        resize();
        resetGameState();
        startScreen.classList.add('hidden');
        gameOverScreen.classList.add('hidden');
        running = true;
        if (animationId) cancelAnimationFrame(animationId);
        animationId = requestAnimationFrame(loop);
      }

      function endGame(byBomb) {
        if (!running) return;
        running = false;
        if (animationId) cancelAnimationFrame(animationId);

        finalScoreEl.textContent = score;
        finalComboEl.textContent = 'x' + bestCombo;
        gameOverEmojiEl.textContent = byBomb ? '💣' : '💥';

        saveScore(score);

        setTimeout(() => {
          gameOverScreen.classList.remove('hidden');
        }, 350);
      }

      const leaderboardScreen = document.getElementById('leaderboardScreen');
      const leaderboardList = document.getElementById('leaderboardList');
      const savePrompt = document.getElementById('savePrompt');

      function loadLeaderboard() {
        fetch('?action=leaderboard', { cache: 'no-store' })
          .then(response => response.json())
          .then(data => {
            leaderboardList.replaceChildren();
            if (!data.scores || data.scores.length === 0) {
              const emptyItem = document.createElement('li');
              emptyItem.className = 'leaderboard-empty';
              emptyItem.textContent = 'Aucun score enregistré pour le moment.';
              leaderboardList.appendChild(emptyItem);
              return;
            }
            data.scores.forEach((entry, index) => {
              const item = document.createElement('li');
              const player = document.createElement('span');
              const score = document.createElement('strong');
              player.textContent = (index + 1) + '. ' + entry.player;
              score.textContent = entry.best_score;
              item.append(player, score);
              leaderboardList.appendChild(item);
            });
          })
          .catch(() => {
            leaderboardList.replaceChildren();
            const errorItem = document.createElement('li');
            errorItem.className = 'leaderboard-empty';
            errorItem.textContent = 'Classement momentanément indisponible.';
            leaderboardList.appendChild(errorItem);
          });
      }

      function saveScore(finalScore) {
        const body = 'score=' + encodeURIComponent(finalScore);
        fetch('?action=save_score', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF_TOKEN },
          body: body
        })
          .then(r => r.json())
          .then(data => {
            if (data.requires_login) {
              if (finalScore > 0) {
                try {
                  sessionStorage.setItem('fn_pending_score', String(finalScore));
                } catch (error) {
                }
                savePrompt.classList.remove('hidden');
              }
              return;
            }
            highscore = data.highscore;
            hiscoreValueEl.textContent = highscore;
            if (data.is_new_record && finalScore > 0) {
              recordMsgEl.classList.remove('hidden');
            } else {
              recordMsgEl.classList.add('hidden');
            }
            loadLeaderboard();
          })
          .catch(() => {
            // Si la requête échoue (offline), on met à jour localement seulement
            if (finalScore > highscore) {
              highscore = finalScore;
              hiscoreValueEl.textContent = highscore;
              recordMsgEl.classList.remove('hidden');
            } else {
              recordMsgEl.classList.add('hidden');
            }
          });
      }

      document.getElementById('leaderboardToggle').addEventListener('click', () => {
        leaderboardScreen.classList.remove('hidden');
        loadLeaderboard();
      });
      document.getElementById('startLeaderboardBtn').addEventListener('click', () => {
        leaderboardScreen.classList.remove('hidden');
        loadLeaderboard();
      });
      document.getElementById('closeLeaderboard').addEventListener('click', () => {
        leaderboardScreen.classList.add('hidden');
      });
      document.getElementById('dismissSavePrompt').addEventListener('click', () => {
        savePrompt.classList.add('hidden');
      });
      document.getElementById('loginToSave').addEventListener('click', () => {
        try {
          sessionStorage.setItem('fn_pending_score', String(score));
        } catch (error) {
        }
      });

      loadLeaderboard();
      window.setInterval(() => {
        if (!document.hidden) loadLeaderboard();
      }, 15000);

      if (IS_LOGGED_IN) {
        try {
          const pendingScore = Number(sessionStorage.getItem('fn_pending_score') || 0);
          if (pendingScore > 0) {
            sessionStorage.removeItem('fn_pending_score');
            saveScore(pendingScore);
          }
        } catch (error) {
        }
      }

      document.getElementById('startBtn').addEventListener('click', startGame);
      document.getElementById('restartBtn').addEventListener('click', startGame);

      // Empêcher le pull-to-refresh / zoom sur mobile
      document.addEventListener('gesturestart', e => e.preventDefault());
      document.addEventListener('dblclick', e => e.preventDefault());

    })();
  </script>

</body>

</html>