document.addEventListener('DOMContentLoaded', function () {
    var metaToken = document.querySelector('meta[name="csrf-token"]');
    var csrf = metaToken ? metaToken.content : '';

    /* ---------- helpers ---------- */
    function post(url, data) {
        var body = new URLSearchParams();
        body.set('csrf_token', csrf);
        Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        }).then(function (res) {
            return res.json().then(function (j) { return j; });
        });
    }

    /* ---------- confirmation modal (returns a Promise<boolean>) ---------- */
    var modal     = document.getElementById('confirm-modal');
    var titleEl   = document.getElementById('confirm-title');
    var messageEl = document.getElementById('confirm-message');
    var okBtn     = document.getElementById('confirm-ok');
    var cancelBtn = document.getElementById('confirm-cancel');

    function confirmDialog(opts) {
        return new Promise(function (resolve) {
            titleEl.textContent   = opts.title;
            messageEl.textContent = opts.message;
            okBtn.textContent     = opts.confirmText || 'Confirm';
            okBtn.classList.toggle('btn-danger', !!opts.danger);
            okBtn.classList.toggle('btn-primary', !opts.danger);
            modal.hidden = false;
            cancelBtn.focus();

            function close(result) {
                modal.hidden = true;
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey);
                resolve(result);
            }
            function onOk()     { close(true); }
            function onCancel() { close(false); }
            function onBackdrop(e) { if (e.target === modal) close(false); }
            function onKey(e) { if (e.key === 'Escape') close(false); }

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            modal.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey);
        });
    }


    /* ---------- flag form scan animation ---------- */
    function restartClass(el, cls) {       // re-trigger a CSS animation class
        el.classList.remove(cls);
        void el.offsetWidth;
        el.classList.add(cls);
    }

    /* kind: 'ok' (cyan scan) or 'bad' (red scan + red flash) */
    function scanForm(formEl, kind) {
        var old = formEl.querySelector('.scan-line');
        if (old) old.remove();
        formEl.classList.remove('flag-form-ok', 'flag-form-bad');
        void formEl.offsetWidth;

        var line = document.createElement('div');
        line.className = 'scan-line';
        formEl.appendChild(line);
        formEl.classList.add('is-scanning', 'flag-form-' + kind);

        setTimeout(function () {
            line.remove();
            formEl.classList.remove('is-scanning');
        }, 1000);
        setTimeout(function () {
            formEl.classList.remove('flag-form-' + kind);
        }, kind === 'ok' ? 1150 : 950);
    }

    /* ---------- reset progress (sidebar, every page) ---------- */
    var resetBtn = document.getElementById('reset-progress');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            confirmDialog({
                title: 'Reset all progress?',
                message: 'This locks every stage again and wipes your score and any hints you have taken. ' +
                         'You will start over from Challenge 1. This cannot be undone.',
                confirmText: 'Yes, reset everything',
                danger: true,
            }).then(function (yes) {
                if (!yes) return;
                resetBtn.disabled = true;
                post('reset_progress.php', {})
                    .then(function (data) {
                        if (data.success) {
                            window.location.href = 'index.php';
                        } else {
                            resetBtn.disabled = false;
                            alert(data.message || 'Could not reset progress.');
                        }
                    })
                    .catch(function () {
                        resetBtn.disabled = false;
                        alert('Something went wrong. Please try again.');
                    });
            });
        });
    }

    /* ---------- hints (challenge page only) ---------- */
    var hintList = document.querySelector('.hint-list');
    if (hintList) {
        var windowEl   = document.querySelector('.challenge-window');
        var penalty    = parseInt(windowEl.dataset.hintPenalty, 10) || 5;
        var badge      = document.getElementById('points-badge');
        var basePoints = badge ? parseInt(badge.dataset.base, 10) : 0;

        function currentPoints() {
            var taken = hintList.querySelectorAll('.hint-item.is-revealed').length;
            return Math.max(0, basePoints - penalty * taken);
        }

        function updateBadge(points) {
            if (!badge) return;
            badge.textContent = '';
            if (points !== basePoints) {
                var old = document.createElement('s');
                old.className = 'points-old';
                old.textContent = basePoints;
                badge.appendChild(old);
                badge.appendChild(document.createTextNode(' '));
            }
            badge.appendChild(document.createTextNode(points + ' pts'));
        }

        hintList.addEventListener('click', function (e) {
            var btn = e.target.closest('.hint-reveal');
            if (!btn || btn.disabled) return;

            var now  = currentPoints();
            var next = Math.max(0, now - penalty);
            var idx  = btn.dataset.hintIndex;

            confirmDialog({
                title: 'Take hint ' + idx + '?',
                message: 'Do you want to take this hint? You will lose ' + penalty +
                         ' points from this challenge (' + now + ' \u2192 ' + next + ' pts).',
                confirmText: 'Take hint',
            }).then(function (yes) {
                if (!yes) return;
                btn.disabled = true;
                post('reveal_hint.php', { hint_id: btn.dataset.hintId })
                    .then(function (data) {
                        if (!data.success) {
                            btn.disabled = false;
                            alert(data.message || 'Could not reveal the hint.');
                            return;
                        }
                        // Replace the button with the revealed hint (server-escaped HTML).
                        var div = document.createElement('div');
                        div.className = 'hint-item is-revealed';
                        div.dataset.hintIndex = idx;
                        div.innerHTML = '<span class="hint-label">Hint ' + idx + '</span>' +
                                        '<div class="hint-text">' + data.hint_text_html + '</div>';
                        btn.replaceWith(div);

                        // Unlock the following hint, if there is one.
                        var nextBtn = hintList.querySelector('.hint-reveal.is-locked');
                        if (nextBtn) {
                            nextBtn.classList.remove('is-locked');
                            nextBtn.disabled = false;
                            nextBtn.removeAttribute('title');
                            nextBtn.querySelector('.hint-cta').textContent = 'Click to reveal this hint';
                        }
                        updateBadge(data.points);
                    })
                    .catch(function () {
                        btn.disabled = false;
                        alert('Something went wrong. Please try again.');
                    });
            });
        });
    }

    /* ---------- flag form (challenge page only) ---------- */
    var form = document.getElementById('flag-form');
    if (!form) return;

    var flagInput   = document.getElementById('flag-input');
    var submitBtn   = document.getElementById('flag-submit');
    var errorEl     = document.getElementById('flag-error');
    var successEl   = document.getElementById('flag-success');
    var challengeNo = document.getElementById('challenge_number').value;

    function showError(msg) {
        successEl.hidden = true;
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }

    function wrongFlag(msg) {
        showError(msg);
        restartClass(flagInput, 'is-wrong');
        scanForm(form, 'bad');
    }

    function showSuccess(msg) {
        errorEl.hidden = true;
        successEl.textContent = msg;
        successEl.hidden = false;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorEl.hidden = true;
        successEl.hidden = true;
        flagInput.classList.remove('is-wrong');

        var flag = flagInput.value.trim();
        if (!flag) {
            showError('Please enter a flag.');
            return;
        }

        submitBtn.disabled = true;

        post('submit_flag.php', { challenge_number: challengeNo, flag: flag })
            .then(function (data) {
                if (data.success) {
                    var pts = parseInt(data.points_awarded, 10) || 0;
                    showSuccess(pts > 0
                        ? '\u2713 Flag correct \u2014 +' + pts + ' pts'
                        : '\u2713 Flag correct');
                    flagInput.disabled = true;
                    submitBtn.disabled = true;
                    scanForm(form, 'ok');

                    // Let the scan finish and the result be read, then move
                    // on to the next unlocked challenge (or the
                    // congratulations page if that was the last one).
                    setTimeout(function () {
                        if (data.all_completed) {
                            window.location.href = 'congrats.php';
                        } else if (data.next_unlocked) {
                            window.location.href = 'challenge.php?id=' + data.next_unlocked;
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    wrongFlag(data.message || 'Incorrect flag. Try again.');
                    submitBtn.disabled = false;
                }
            })
            .catch(function () {
                showError('Something went wrong. Please try again.');
                submitBtn.disabled = false;
            });
    });
});
