<?php
/**
 * Registrony — Helper per i campi del form
 *
 * Ogni funzione restituisce (echo) HTML pronto per l'uso, con:
 *  - label accessibile
 *  - input / select / textarea
 *  - div .field-hint  (suggerimento contestuale fisso)
 *  - div .field-error (errore JS, inizialmente nascosto)
 *  - icona di stato ✓/✗ animata via CSS (nascosta sui <select> per non
 *    sovrapporsi alla freccia nativa del dropdown)
 *
 * Tutte le stringhe visibili provengono dal file lang/it.php tramite L().
 */

if (!function_exists('L')) {
    require_once __DIR__ . '/app.php';
}

/* ============================================================
   FIELD — TEXT / EMAIL / PASSWORD / NUMBER / DATE / TIME
   ============================================================ */

function formField(string $id, string $label, array $opts = []): void {
    $type         = $opts['type']         ?? 'text';
    $value        = htmlspecialchars($opts['value'] ?? '');
    $placeholder  = htmlspecialchars($opts['placeholder'] ?? '');
    $hint         = $opts['hint']         ?? '';
    $required     = !empty($opts['required']);
    $maxlength    = isset($opts['max'])   ? (int)$opts['max'] : null;
    $min          = $opts['min']          ?? null;
    $step         = $opts['step']         ?? null;
    $pattern      = $opts['pattern']      ?? null;
    $autocomplete = $opts['autocomplete'] ?? 'off';
    $extra        = $opts['extra']        ?? '';
    $counter      = !empty($opts['counter']) && $maxlength;
    $errId        = $opts['error_id']     ?? 'err_' . $id;
    $reqMark      = $required ? '<span class="req-mark" aria-hidden="true">*</span>' : '';
    $maxAttr      = $maxlength ? "maxlength=\"$maxlength\"" : '';
    $minAttr      = $min  !== null ? "min=\"$min\"" : '';
    $stepAttr     = $step !== null ? "step=\"$step\"" : '';
    $patAttr      = $pattern ? "pattern=\"$pattern\"" : '';
    $reqAttr      = $required ? 'required' : '';
    $counterAttr  = $counter ? "data-counter=\"$id\" data-max=\"$maxlength\"" : '';

    echo <<<HTML
<div class="form-group fg-{$id}">
  <label for="{$id}">{$label}{$reqMark}</label>
  <div class="field-wrap">
    <input
      type="{$type}"
      id="{$id}"
      name="{$id}"
      class="form-control"
      value="{$value}"
      placeholder="{$placeholder}"
      autocomplete="{$autocomplete}"
      {$maxAttr} {$minAttr} {$stepAttr} {$patAttr} {$reqAttr} {$extra}
    >
    <span class="field-status" aria-hidden="true"></span>
  </div>
HTML;

    if ($hint) {
        echo "  <div class=\"field-hint\">{$hint}</div>\n";
    }
    if ($counter) {
        echo "  <div class=\"field-hint char-counter\" id=\"cnt_{$id}\" data-counter=\"{$id}\" data-max=\"{$maxlength}\">0 / {$maxlength}</div>\n";
    }
    echo "  <div class=\"field-error\" id=\"{$errId}\" role=\"alert\"></div>\n";
    echo "</div>\n";
}

/* ============================================================
   FIELD — TEXTAREA
   ============================================================ */
function formTextarea(string $id, string $label, array $opts = []): void {
    $value       = htmlspecialchars($opts['value'] ?? '');
    $placeholder = htmlspecialchars($opts['placeholder'] ?? '');
    $hint        = $opts['hint']     ?? '';
    $required    = !empty($opts['required']);
    $rows        = $opts['rows']     ?? 3;
    $maxlength   = isset($opts['max']) ? (int)$opts['max'] : null;
    $counter     = !empty($opts['counter']) && $maxlength;
    $extra       = $opts['extra']    ?? '';
    $errId       = $opts['error_id'] ?? 'err_' . $id;
    $reqMark     = $required ? '<span class="req-mark" aria-hidden="true">*</span>' : '';
    $maxAttr     = $maxlength ? "maxlength=\"$maxlength\"" : '';
    $reqAttr     = $required ? 'required' : '';

    echo <<<HTML
<div class="form-group fg-{$id}">
  <label for="{$id}">{$label}{$reqMark}</label>
  <div class="field-wrap">
    <textarea
      id="{$id}"
      name="{$id}"
      class="form-control"
      rows="{$rows}"
      placeholder="{$placeholder}"
      {$maxAttr} {$reqAttr} {$extra}
    >{$value}</textarea>
    <span class="field-status field-status--ta" aria-hidden="true"></span>
  </div>
HTML;

    if ($hint) {
        echo "  <div class=\"field-hint\">{$hint}</div>\n";
    }
    if ($counter) {
        echo "  <div class=\"field-hint char-counter\" id=\"cnt_{$id}\" data-counter=\"{$id}\" data-max=\"{$maxlength}\">0 / {$maxlength}</div>\n";
    }
    echo "  <div class=\"field-error\" id=\"{$errId}\" role=\"alert\"></div>\n";
    echo "</div>\n";
}

/* ============================================================
   FIELD — SELECT
   ============================================================ */
function formSelect(string $id, string $label, array $options, array $opts = []): void {
    $selected  = (string)($opts['selected'] ?? '');
    $hint      = $opts['hint']     ?? '';
    $required  = !empty($opts['required']);
    $extra     = $opts['extra']    ?? '';
    $errId     = $opts['error_id'] ?? 'err_' . $id;
    $reqMark   = $required ? '<span class="req-mark" aria-hidden="true">*</span>' : '';
    $reqAttr   = $required ? 'required' : '';

    echo <<<HTML
<div class="form-group fg-{$id}">
  <label for="{$id}">{$label}{$reqMark}</label>
  <div class="field-wrap field-wrap--select">
    <select id="{$id}" name="{$id}" class="form-control" {$reqAttr} {$extra}>
HTML;

    foreach ($options as $val => $lbl) {
        if (is_array($lbl)) {
            $v    = htmlspecialchars((string)$lbl['value']);
            $l    = htmlspecialchars((string)$lbl['label']);
            $dis  = !empty($lbl['disabled']) ? 'disabled' : '';
            $sel  = ($v !== '' && $v === $selected) ? 'selected' : '';
        } else {
            $v   = htmlspecialchars((string)$val);
            $l   = htmlspecialchars((string)$lbl);
            $dis = '';
            $sel = ($v !== '' && $v === $selected) ? 'selected' : '';
        }
        echo "      <option value=\"{$v}\" {$sel} {$dis}>{$l}</option>\n";
    }

    echo "    </select>\n";
    /* Nessuna .field-status qui: il bordo is-valid/is-invalid è sufficiente
       e l'icona si sovrapporrebbe alla freccia nativa del dropdown. */
    echo "  </div>\n";

    if ($hint) {
        echo "  <div class=\"field-hint\">{$hint}</div>\n";
    }
    echo "  <div class=\"field-error\" id=\"{$errId}\" role=\"alert\"></div>\n";
    echo "</div>\n";
}

/* ============================================================
   FIELD — TELEFONO CON PREFISSO
   ============================================================ */
function formTelefono(string $idNumero, string $label, array $opts = []): void {
    $L         = lang();
    $prefissi  = $L['prefissi'] ?? ['+39' => '🇮🇹 +39'];
    $fullValue = $opts['value'] ?? '';
    $hint      = $opts['hint'] ?? $L['utenti_telefono_hint'] ?? '';
    $errId     = $opts['error_id'] ?? 'err_' . $idNumero;
    $required  = !empty($opts['required']);
    $reqMark   = $required ? '<span class="req-mark" aria-hidden="true">*</span>' : '';

    $savedPrefix = '+39';
    $savedNumber = $fullValue;
    foreach (array_keys($prefissi) as $p) {
        if (str_starts_with($fullValue, $p . ' ')) {
            $savedPrefix = $p;
            $savedNumber = substr($fullValue, strlen($p) + 1);
            break;
        } elseif (str_starts_with($fullValue, $p)) {
            $savedPrefix = $p;
            $savedNumber = substr($fullValue, strlen($p));
            break;
        }
    }
    $savedNumber = htmlspecialchars(trim($savedNumber));

    echo "<div class=\"form-group fg-{$idNumero}\">\n";
    echo "  <label for=\"{$idNumero}\">{$label}{$reqMark}</label>\n";
    echo "  <div class=\"field-wrap tel-wrap\">\n";
    echo "    <select id=\"tel_prefix\" name=\"tel_prefix\" class=\"form-control tel-prefix\" aria-label=\"" . htmlspecialchars($L['utenti_prefisso_label'] ?? 'Prefisso') . "\">\n";
    foreach ($prefissi as $val => $lbl) {
        $sel = ($val === $savedPrefix) ? 'selected' : '';
        echo "      <option value=\"" . htmlspecialchars($val) . "\" {$sel}>" . htmlspecialchars($lbl) . "</option>\n";
    }
    echo "    </select>\n";

    $placeholder = htmlspecialchars($opts['placeholder'] ?? '333-1234567');
    echo <<<HTML
    <input
      type="tel"
      id="{$idNumero}"
      name="{$idNumero}"
      class="form-control tel-number"
      value="{$savedNumber}"
      placeholder="{$placeholder}"
      maxlength="20"
      autocomplete="tel-national"
    >
    <span class="field-status" aria-hidden="true"></span>
  </div>
HTML;

    echo "  <input type=\"hidden\" id=\"{$idNumero}_full\" name=\"telefono\" value=\"" . htmlspecialchars($fullValue) . "\">\n";
    if ($hint) {
        echo "  <div class=\"field-hint\">{$hint}</div>\n";
    }
    echo "  <div class=\"field-error\" id=\"{$errId}\" role=\"alert\"></div>\n";
    echo "</div>\n";
}

/* ============================================================
   FIELD — SLOT ORARIO
   ============================================================ */
function formOrario(string $id, string $label, array $opts = []): void {
    $selected = $opts['value'] ?? '';
    $hint     = $opts['hint']  ?? '';
    $required = !empty($opts['required']);
    $errId    = $opts['error_id'] ?? 'err_' . $id;
    $extra    = $opts['extra']    ?? '';
    $reqMark  = $required ? '<span class="req-mark" aria-hidden="true">*</span>' : '';
    $reqAttr  = $required ? 'required' : '';

    $slots = [];
    $start = strtotime('07:30');
    $end   = strtotime('20:00');
    for ($t = $start; $t <= $end; $t += 5 * 60) {
        $slots[] = date('H:i', $t);
    }

    echo "<div class=\"form-group fg-{$id}\">\n";
    echo "  <label for=\"{$id}\">{$label}{$reqMark}</label>\n";
    echo "  <div class=\"field-wrap field-wrap--select field-wrap--orario\">\n";
    echo "    <select id=\"{$id}\" name=\"{$id}\" class=\"form-control\" {$reqAttr} {$extra}>\n";
    echo "      <option value=\"\">-- Seleziona orario --</option>\n";
    foreach ($slots as $slot) {
        $sel = ($slot === substr($selected, 0, 5)) ? 'selected' : '';
        echo "      <option value=\"{$slot}\" {$sel}>{$slot}</option>\n";
    }
    echo "    </select>\n";
    /* Nessuna .field-status: la freccia nativa del select occupa lo spazio */
    echo "  </div>\n";

    if ($hint) {
        echo "  <div class=\"field-hint\">{$hint}</div>\n";
    }
    echo "  <div class=\"field-error\" id=\"{$errId}\" role=\"alert\"></div>\n";
    echo "</div>\n";
}

/* ============================================================
   FIELD — CHECKBOX
   ============================================================ */
function formCheckbox(string $id, string $label, bool $checked = false, array $opts = []): void {
    $value = $opts['value'] ?? '1';
    $extra = $opts['extra'] ?? '';
    $chk   = $checked ? 'checked' : '';

    echo <<<HTML
<div class="form-group form-group--check">
  <label class="check-label" for="{$id}">
    <input type="checkbox" id="{$id}" name="{$id}" value="{$value}" {$chk} {$extra}>
    <span class="check-box"></span>
    {$label}
  </label>
</div>
HTML;
}

/* ============================================================
   CSS aggiuntivo per controlli contestuali
   ============================================================ */
function formFieldStyles(): void { ?>
<style>
/* ---- Contenitore campo ---- */
.field-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
/* Input/textarea: padding-right per icona stato */
.field-wrap .form-control:not(select) {
    flex: 1;
    padding-right: 36px;
}
/* Select: nessun padding extra a destra, la freccia nativa usa quello spazio.
   L'icona .field-status NON viene emessa nei select (vedi formSelect). */
.field-wrap--select .form-control {
    flex: 1;
    padding-right: 12px; /* reset al default */
}
textarea.form-control { padding-right: 12px; }

/* ---- Icona stato (input, non select) ---- */
.field-status {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    opacity: 0;
    transition: opacity .2s;
    pointer-events: none;
}
.field-status--ta {
    top: 14px;
    transform: none;
}
.form-control.is-valid   ~ .field-status::after { content: '✓'; color: var(--success); }
.form-control.is-invalid ~ .field-status::after { content: '✗'; color: var(--danger);  }
.form-control.is-valid   ~ .field-status,
.form-control.is-invalid ~ .field-status { opacity: 1; }

/* ---- Bordi validazione ---- */
.form-control.is-valid   { border-color: var(--success); box-shadow: 0 0 0 3px rgba(22,163,74,.12); }
.form-control.is-invalid { border-color: var(--danger);  box-shadow: 0 0 0 3px rgba(220,38,38,.12); }

/* ---- Hint e errore ---- */
.field-hint {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 4px;
}
.field-error {
    font-size: 12px;
    color: var(--danger);
    margin-top: 3px;
    display: none;
    animation: errIn .15s ease;
}
@keyframes errIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }

/* ---- Asterisco obbligatorio ---- */
.req-mark { color: var(--danger); margin-left: 2px; font-weight:700; }

/* ---- Counter caratteri ---- */
.char-counter { font-variant-numeric: tabular-nums; }
.char-counter.warn  { color: var(--warning); }
.char-counter.over  { color: var(--danger); font-weight:600; }

/* ---- Telefono prefisso+numero ---- */
.tel-wrap { gap: 8px; }
.tel-prefix { flex: 0 0 170px; padding-right: 12px !important; }
.tel-number { flex: 1; }
@media (max-width: 480px) {
    .tel-wrap { flex-wrap: wrap; }
    .tel-prefix { flex: 0 0 100%; }
    .tel-number { flex: 0 0 100%; }
}

/* ---- Checkbox custom ---- */
.form-group--check { margin-bottom: 16px; }
.check-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
    font-size: 14px;
    user-select: none;
}
.check-label input[type=checkbox] { display: none; }
.check-box {
    width: 18px; height: 18px;
    border: 2px solid var(--border);
    border-radius: 4px;
    background: var(--bg-white);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: border-color .15s, background .15s;
}
.check-label input:checked + .check-box {
    background: var(--primary);
    border-color: var(--primary);
}
.check-label input:checked + .check-box::after {
    content: '';
    width: 10px; height: 6px;
    border-left: 2px solid #fff;
    border-bottom: 2px solid #fff;
    transform: rotate(-45deg) translateY(-1px);
}
.check-label:hover .check-box { border-color: var(--primary); }
</style>
<?php }

/* ============================================================
   JS per validazione live + counter + telefono
   ============================================================ */
function formFieldScripts(): void { ?>
<script>
(function () {
    function showErr(inputEl, errId, msg) {
        const e = document.getElementById(errId);
        if (!e) return;
        inputEl.classList.add('is-invalid');
        inputEl.classList.remove('is-valid');
        e.textContent = msg;
        e.style.display = 'block';
    }
    function clearErr(inputEl, errId) {
        const e = document.getElementById(errId);
        if (!e) return;
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        e.textContent = '';
        e.style.display = 'none';
    }
    window.formShowErr  = showErr;
    window.formClearErr = clearErr;

    /* Contatori caratteri */
    document.querySelectorAll('[data-counter]').forEach(function (cnt) {
        const target = document.getElementById(cnt.dataset.counter);
        const max    = parseInt(cnt.dataset.max);
        if (!target) return;
        function update() {
            const len = target.value.length;
            cnt.textContent = len + ' / ' + max;
            cnt.classList.toggle('warn', len > max * 0.85 && len < max);
            cnt.classList.toggle('over', len >= max);
        }
        target.addEventListener('input', update);
        update();
    });

    /* Telefono: unisci prefisso+numero in hidden */
    document.querySelectorAll('.tel-wrap').forEach(function (wrap) {
        const prefix = wrap.querySelector('.tel-prefix');
        const number = wrap.querySelector('.tel-number');
        if (!prefix || !number) return;
        const hidden = document.getElementById(number.id + '_full');
        function combine() {
            if (hidden) hidden.value = number.value.trim()
                ? prefix.value + ' ' + number.value.trim()
                : '';
        }
        prefix.addEventListener('change', combine);
        number.addEventListener('input',  combine);
        combine();
    });

    /* Validazione live su blur */
    document.querySelectorAll('.form-control[required]').forEach(function (el) {
        el.addEventListener('blur', function () {
            const errId = 'err_' + el.id;
            if (!el.value.trim()) {
                showErr(el, errId, 'Campo obbligatorio.');
            } else {
                clearErr(el, errId);
            }
        });
    });

    /* Email live */
    document.querySelectorAll('input[type=email]').forEach(function (el) {
        el.addEventListener('blur', function () {
            const errId = 'err_' + el.id;
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (el.value && !re.test(el.value)) {
                showErr(el, errId, 'Inserisci un indirizzo email valido.');
            } else if (el.value) {
                clearErr(el, errId);
            }
        });
    });

    /* Ora uscita > ingresso */
    const oraI = document.getElementById('ora_ingresso');
    const oraU = document.getElementById('ora_uscita');
    if (oraI && oraU) {
        function checkOre() {
            if (oraU.value && oraU.value <= oraI.value) {
                showErr(oraU, 'err_ora_uscita', "L'ora di uscita deve essere successiva all'ingresso.");
            } else {
                clearErr(oraU, 'err_ora_uscita');
            }
        }
        oraI.addEventListener('change', checkOre);
        oraU.addEventListener('change', checkOre);
    }

    /* Sync docenti (titolare ≠ compresenza) */
    const selTit  = document.getElementById('docente_titolare');
    const selComp = document.getElementById('docente_compresenza');
    if (selTit && selComp) {
        function syncDocenti() {
            const v = selTit.value;
            Array.from(selComp.options).forEach(function (opt) {
                opt.disabled = (opt.value !== '' && opt.value === v);
            });
            if (selComp.value === v) selComp.value = '';
        }
        selTit.addEventListener('change', syncDocenti);
        syncDocenti();
    }

    /* Soglia vs quantità materiale */
    const qEl = document.getElementById('quantita_disponibile');
    const sEl = document.getElementById('soglia_minima');
    if (qEl && sEl) {
        function checkQS() {
            if (qEl.value !== '' && sEl.value !== '') {
                if (parseFloat(sEl.value) > parseFloat(qEl.value)) {
                    showErr(sEl, 'err_soglia_minima', 'La soglia non può superare la quantità disponibile.');
                } else {
                    clearErr(sEl, 'err_soglia_minima');
                }
            }
        }
        qEl.addEventListener('input', checkQS);
        sEl.addEventListener('input', checkQS);
    }

    /* Mostra/nascondi password */
    window.togglePwd = function (inputId) {
        const f = document.getElementById(inputId || 'password');
        if (f) f.type = f.type === 'password' ? 'text' : 'password';
    };

    /* Uppercase nome classe */
    const nomeClasse = document.getElementById('nome');
    if (nomeClasse && nomeClasse.closest('.fg-nome')) {
        nomeClasse.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    }

    /* Indicatore forza password */
    const pwdInput = document.getElementById('password') || document.getElementById('new_password');
    const pwdBar   = document.getElementById('pwdBar');
    const pwdLabel = document.getElementById('pwdLabel');
    const pwdWrap  = document.getElementById('pwdStrength');
    if (pwdInput && pwdBar && pwdWrap) {
        const levels = [
            { w:'20%',  bg:'var(--danger)',  l:'Molto debole' },
            { w:'40%',  bg:'var(--warning)', l:'Debole' },
            { w:'60%',  bg:'#f59e0b',        l:'Accettabile' },
            { w:'80%',  bg:'var(--info)',     l:'Forte' },
            { w:'100%', bg:'var(--success)',  l:'Molto forte' },
        ];
        pwdInput.addEventListener('input', function () {
            const v = pwdInput.value;
            pwdWrap.style.display = v ? 'block' : 'none';
            let score = 0;
            if (v.length >= 6)          score++;
            if (v.length >= 10)         score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const lv = levels[Math.max(0, score - 1)];
            pwdBar.style.width      = lv.w;
            pwdBar.style.background = lv.bg;
            if (pwdLabel) { pwdLabel.textContent = lv.l; pwdLabel.style.color = lv.bg; }
        });
    }

})();
</script>
<?php }
