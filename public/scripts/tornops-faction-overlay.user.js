// ==UserScript==
// @name         TornOps Faction Overlay
// @namespace    https://tornops.net
// @version      3.3.0
// @description  Travel ETAs, FF scores, sorting on Torn faction page
// @author       bafplus
// @match        https://www.torn.com/factions.php*
// @match        https://torn.com/factions.php*
// @icon         https://www.torn.com/favicon.ico
// @grant        GM_setValue
// @grant        GM_getValue
// @grant        GM_xmlhttpRequest
// @run-at       document-idle
// ==/UserScript==

(function() {
    'use strict';
    console.log('[TornOps] v3.3 loaded');
    console.log('[TornOps] URL:', location.href);

    // Check if API key and token are configured
    const KEY_URL = 'tornops_url';
    const KEY_TOKEN = 'tornops_token';
    let url = GM_getValue(KEY_URL, '');
    let token = GM_getValue(KEY_TOKEN, '');
    console.log('[TornOps] Configured:', !!url, !!token);
    let memberMap = {};
    let isRunning = false;
    let domObserver = null;

    const s = document.createElement('style');
    s.textContent = '.tornops-btn{position:fixed;top:10px;right:10px;z-index:9999;background:#16213e;color:#e2e8f0;border:1px solid #334155;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;opacity:0.6;z-index:9999}.tornops-btn:hover{opacity:1}.tornops-modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:10000}.tornops-box{background:#16213e;border:1px solid #334155;border-radius:12px;padding:24px;max-width:400px;width:90%;color:#e2e8f0}.tornops-box h2{margin:0 0 16px;font-size:18px}.tornops-box label{display:block;margin-bottom:4px;color:#94a3b8;font-size:12px}.tornops-box input{width:100%;padding:8px 12px;background:#1e293b;border:1px solid #475569;border-radius:6px;color:#e2e8f0;font-size:13px;margin-bottom:12px;box-sizing:border-box}.tornops-box button{background:#2563eb;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px}.tornops-ff{font-size:9px;color:#a78bfa;display:block}';
    document.head.appendChild(s);

    function showConfig() {
        const el = document.createElement('div');
        el.className = 'tornops-modal';
        el.innerHTML = '<div class="tornops-box"><h2>\u2699 TornOps</h2><label>Instance URL</label><input id="tu" value="' + url + '" placeholder="https://trr.tornops.net"><label>API Token</label><input id="tt" value="' + token + '" placeholder="Your token"><div style="text-align:right"><button id="ts">Save</button></div><div class="err" id="te"></div></div>';
        document.body.appendChild(el);
        document.getElementById('ts').onclick = function() {
            var u = document.getElementById('tu').value.trim().replace(/\/+$/, '');
            var t = document.getElementById('tt').value.trim();
            if (!u || !t) { document.getElementById('te').textContent = 'Both required'; return; }
            url = u; token = t;
            GM_setValue(KEY_URL, u);
            GM_setValue(KEY_TOKEN, t);
            document.body.removeChild(el);
            load();
        };
    }

    var btn = document.createElement('button');
    btn.className = 'tornops-btn';
    btn.textContent = '\u2699 TornOps';
    btn.onclick = showConfig;
    document.body.appendChild(btn);
    if (!url || !token) { showConfig(); return; }

    function load() {
        GM_xmlhttpRequest({
            method: 'GET',
            url: url + '/api/faction/members?api_token=' + token,
            onload: function(r) {
                try {
                    var d = JSON.parse(r.responseText);
                    if (d.error) { console.log('[TornOps]', d.error); return; }
                    memberMap = {};
                    for (var i = 0; i < d.members.length; i++) {
                        var m = d.members[i];
                        memberMap[m.player_id] = m;
                    }
                    console.log('[TornOps] Got', d.members.length, 'members');
                    run();
                } catch(e) { console.log('[TornOps] Parse error', e); }
            }
        });
    }

    function fmtTime(s) {
        if (s <= 0) return '\u2705';
        var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sc = s % 60;
        if (h > 0) return h + 'h ' + m + 'm';
        if (m > 0) return m + 'm ' + sc + 's';
        return sc + 's';
    }

    function run() {
        if (isRunning) return;
        isRunning = true;
        if (domObserver) domObserver.disconnect();
        var now = Math.floor(Date.now() / 1000);

        document.querySelectorAll('ul.members-list').forEach(function(ul) {
            var items = [];
            ul.querySelectorAll('li.your, li.enemy').forEach(function(li) {
                var link = li.querySelector('a[href*="/profiles.php?XID="]');
                if (!link) return;
                var pid = (link.getAttribute('href') || '').split('XID=')[1];
                if (!pid) return;
                var member = memberMap[pid];

                if (member) {
                    // FF score in level column
                    if (member.ff_score > 0) {
                        var levelDiv = li.querySelector('div.level');
                        if (levelDiv && !levelDiv.querySelector('.tornops-ff')) {
                            var ff = document.createElement('span');
                            ff.className = 'tornops-ff';
                            ff.textContent = '\u2B50' + member.ff_score;
                            levelDiv.appendChild(ff);
                        }
                    }

                    // Compact status
                    var sd = li.querySelector('div.status');
                    if (sd && !sd.getAttribute('data-done')) {
                        sd.setAttribute('data-done', '1');
                        var desc = member.status_description || '';
                        var compact = '';

                        if (member.status_color === 'red') {
                            if (member.country && desc.indexOf('In hospital') < 0 && desc.indexOf('In a') >= 0) {
                                compact = member.country;
                            }
                            if (member.until) {
                                var left = member.until - now;
                                if (left > 0) {
                                    compact += (compact ? ' ' : '') + fmtTime(left);
                                    sd.setAttribute('data-until', member.until);
                                } else {
                                    compact += '\u2705';
                                }
                            } else {
                                // No until timestamp - show raw description briefly
                                compact += desc.replace(/In hospital for /, '').replace(/In a /, '').replace(/ hospital.*/, '').trim();
                            }
                        } else if (member.eta) {
                            compact = '\u2708' + (member.country || '?') + ' ' + fmtTime(member.eta - now);
                            sd.setAttribute('data-eta', member.eta);
                        } else if (member.status_color === 'blue' && member.country) {
                            compact = '\uD83D\uDCCD' + member.country;
                        }

                        if (compact) {
                            if (member.team === 'enemy') {
                                sd.innerHTML = compact + ' <a href="https://www.torn.com/page.php?sid=attack&user2ID=' + pid + '" target="_blank" style="color:#dc2626;font-weight:700;text-decoration:none;">\u2694</a>';
                            } else {
                                sd.textContent = compact;
                            }
                        }
                    }
                }

                items.push({ li: li, pid: pid, member: member });
            });

            // Sort: green first, then red (by until), then blue with eta (by eta), then blue without eta, then rest
            items.sort(function(a, b) {
                var va = sortVal(a.member), vb = sortVal(b.member);
                if (va !== vb) return va - vb;
                return (a.member && a.member.name || '').localeCompare(b.member && b.member.name || '');
            });
            var dirty = false;
            var cur = ul.querySelectorAll('li.your, li.enemy');
            for (var i = 0; i < items.length; i++) {
                if (cur[i] !== items[i].li) { dirty = true; break; }
            }
            if (dirty) {
                var frag = document.createDocumentFragment();
                for (var i = 0; i < items.length; i++) frag.appendChild(items[i].li);
                ul.appendChild(frag);
            }
        });

        isRunning = false;
        if (domObserver) {
            var wl = document.querySelector('#faction_war_list_id');
            if (wl) domObserver.observe(wl, { childList: true, subtree: true });
        }
    }

    function sortVal(m) {
        if (!m) return 999;
        if (m.status_color === 'green') return 1;
        if (m.status_color === 'red') return 2;
        if (m.status_color === 'blue') {
            if (m.eta) return 3;
            if (m.country) return 4;
            return 5;
        }
        return 99;
    }

    // Countdown updates (simple interval, no DOM modifications beyond text)
    setInterval(function() {
        var n = Math.floor(Date.now() / 1000);
        document.querySelectorAll('[data-eta]').forEach(function(el) {
            var eta = parseInt(el.dataset.eta);
            if (!eta) return;
            var left = eta - n;
            if (left <= 0) {
                el.removeAttribute('data-eta');
                el.innerHTML = el.innerHTML.replace(/\u2708\w+\s*.*/, '\u2705');
                return;
            }
            var country = (el.textContent.match(/\u2708(\w+)/) || [,''])[1];
            el.innerHTML = '\u2708' + country + ' ' + fmtTime(left) + (el.innerHTML.indexOf('\u2694') > 0 ? ' \u2694' : '');
        });
    }, 1000);

    function tryRun() {
        var target = document.querySelector('#faction_war_list_id') || document.querySelector('ul.members-list');
        if (target && !domObserver) {
            if (obs) obs.disconnect();
            load();
            domObserver = new MutationObserver(function() { if (!isRunning) setTimeout(run, 200); });
            domObserver.observe(target.parentElement || target, { childList: true, subtree: true });
            return true;
        }
        return false;
    }

    if (!tryRun()) {
        var obs = new MutationObserver(function() { tryRun(); });
        obs.observe(document.body, { childList: true, subtree: true });
        setTimeout(function() {
            obs.disconnect();
            if (!domObserver) load();
        }, 8000);
    }
})();
