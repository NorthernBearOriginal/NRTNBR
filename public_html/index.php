<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Миниапп</title>
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--tg-theme-bg-color, #fff);
            color: var(--tg-theme-text-color, #222);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        #main_left_block {
            position: absolute;
            top: 32px;
            left: 32px;
            min-width: 270px;
            background: linear-gradient(120deg, #f8fafc 60%, #e3e9f3 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px 0 rgba(60,60,120,0.10);
            padding: 28px 32px 24px 32px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 18px;
        }
        #welcome_user {
            font-size: 1.25em;
            font-weight: 600;
            margin-bottom: 0.5em;
            color: #2a2a44;
        }
        #welcome_user a:hover {
            border-bottom-color: #2678b6 !important;
        }
        #resources_panel {
            display: flex;
            flex-direction: row;
            gap: 22px;
            width: 100%;
        }
        .resource {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 60px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(80,80,120,0.07);
            padding: 10px 14px 8px 14px;
        }
        .resource-label {
            font-size: 0.97em;
            color: #6a6a8a;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .resource-value {
            font-size: 1.18em;
            font-weight: bold;
            color: #2a2a44;
        }
        @media (max-width: 600px) {
            #main_left_block {
                left: 8px;
                right: 8px;
                min-width: unset;
                padding: 18px 8px 14px 12px;
            }
            #resources_panel {
                gap: 10px;
            }
            .resource {
                min-width: 44px;
                padding: 7px 7px 6px 7px;
            }
        }
    </style>
</head>
<body>
    <div id="main_left_block">
        <div id="welcome_user"></div>
        <div id="resources_panel">
            <div class="resource"><span class="resource-label">🍞 Еда</span><span class="resource-value" id="food_val">...</span></div>
            <div class="resource"><span class="resource-label">🌲 Дерево</span><span class="resource-value" id="wood_val">...</span></div>
            <div class="resource"><span class="resource-label">⛓️ Железо</span><span class="resource-value" id="iron_val">...</span></div>
            <div class="resource"><span class="resource-label">💰 Серебро</span><span class="resource-value" id="silver_val">...</span></div>
            <div class="resource"><span class="resource-label">🪖 Армия</span><span class="resource-value" id="soldiers_val">...</span></div>
        </div>
        <div style="display: flex; flex-direction: row; gap: 14px; align-items: center; margin-top:22px;">
            <button id="build_btn" style="font-size:1.1em; padding:10px 28px; border-radius:8px; background:#4caf50; color:#fff; border:none; cursor:pointer;">Построить</button>
            <button id="chat_btn" style="font-size:1.1em; padding:10px 28px; border-radius:8px; background:#2678b6; color:#fff; border:none; cursor:pointer;">Чат</button>
        </div>
        <div id="buildings_list" style="margin-top:18px; width:100%;">
            <div style="font-weight:500; color:#2a2a44; margin-bottom:8px;">Список строений:</div>
            <div id="buildings_content" style="color:#444; font-size:1em; min-height:24px;">Нет построек</div>
        </div>
    </div>
    <div id="modal_bg" style="display:none; position:fixed; left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:1000;align-items:center;justify-content:center;"><div id="modal_win" style="background:#fff; border-radius:12px; padding:28px 32px; min-width:260px; box-shadow:0 4px 24px 0 rgba(60,60,120,0.13);"><div style="font-size:1.15em; font-weight:600; margin-bottom:18px;">Выберите строение</div><button id="lumbermill_btn" style="display:block;width:100%;margin-bottom:10px;padding:10px 0;font-size:1em;border-radius:7px;border:none;background:#e3e9f3;cursor:pointer;">🌲 Лесопилка<br><span id='lumbermill_cost' style='font-size:0.95em;font-weight:400;'></span></button><button id="farm_btn" style="display:block;width:100%;margin-bottom:10px;padding:10px 0;font-size:1em;border-radius:7px;border:none;background:#e3e9f3;cursor:pointer;">🌾 Ферма<br><span id='farm_cost' style='font-size:0.95em;font-weight:400;'></span></button><button id="ironmine_btn" style="display:block;width:100%;margin-bottom:10px;padding:10px 0;font-size:1em;border-radius:7px;border:none;background:#e3e9f3;cursor:pointer;">⛏️ Железная шахта<br><span id='ironmine_cost' style='font-size:0.95em;font-weight:400;'></span></button><button id="silvermine_btn" style="display:block;width:100%;margin-bottom:10px;padding:10px 0;font-size:1em;border-radius:7px;border:none;background:#e3e9f3;cursor:pointer;">💎 Серебряная шахта<br><span id='silvermine_cost' style='font-size:0.95em;font-weight:400;'></span></button><button id="close_modal_btn" style="display:block;width:100%;padding:8px 0;font-size:1em;border-radius:7px;border:none;background:#eee;cursor:pointer;">Отмена</button></div></div>
    <script>
        let g_user = null;
        let g_buildings = [];
        let g_resources = {};
        let g_buildings_timer = null;
        const BUILDING_META = {
            lumbermill: { icon: '🌲', name: 'Лесопилка', income: 'дерева', build: 'build_lumbermill', baseCost: {food:200}, baseTime:5, resource: 'wood' },
            farm: { icon: '🌾', name: 'Ферма', income: 'еды', build: 'build_farm', baseCost: {wood:200}, baseTime:5, resource: 'food' },
            ironmine: { icon: '⛏️', name: 'Железная шахта', income: 'железа', build: 'build_ironmine', baseCost: {food:200,wood:200}, baseTime:5, resource: 'iron' },
            silvermine: { icon: '💎', name: 'Серебряная шахта', income: 'серебра', build: 'build_silvermine', baseCost: {food:200,wood:200,iron:100}, baseTime:5, resource: 'silver' },
        };
        function getBuildingLevel(type) {
            const b = g_buildings.find(x=>x.type===type);
            return b ? b.level : 1;
        }
        function getBuildingStatus(type) {
            const b = g_buildings.find(x=>x.type===type);
            return b ? b.status : null;
        }
        function getBuildingTime(type) {
            const lvl = getBuildingLevel(type);
            return BUILDING_META[type].baseTime * Math.pow(2, lvl-1);
        }
        function getBuildingCost(type) {
            const lvl = getBuildingLevel(type);
            const base = BUILDING_META[type].baseCost;
            let cost = {};
            for(const k in base) cost[k] = base[k]*Math.pow(2,lvl-1);
            return cost;
        }
        function getBuildingIncome(type) {
            const lvl = getBuildingLevel(type);
            return 100*lvl;
        }
        function costToStr(cost) {
            let arr = [];
            if(cost.food) arr.push(cost.food+' еды');
            if(cost.wood) arr.push(cost.wood+' дерева');
            if(cost.iron) arr.push(cost.iron+' железа');
            return arr.join(', ');
        }
        function updateResourcesUI(data) {
            document.getElementById('food_val').textContent = data.food ?? '0';
            document.getElementById('wood_val').textContent = data.wood ?? '0';
            document.getElementById('iron_val').textContent = data.iron ?? '0';
            document.getElementById('silver_val').textContent = data.silver ?? '0';
            document.getElementById('soldiers_val').textContent = data.soldiers ?? '0';
        }
        function updateBuildingsUI() {
            const cont = document.getElementById('buildings_content');
            if (!g_buildings.length) { cont.textContent = 'Нет построек'; return; }
            cont.innerHTML = g_buildings.map(b => {
                const meta = BUILDING_META[b.type];
                let lvl = b.level || 1;
                let nextCost = {};
                let nextIncome = 100 * lvl;
                let nextTime = meta.baseTime * Math.pow(2, lvl-1);
                if(b.status==='building') {
                    return `${meta.icon} ${meta.name} <span style='color:#888'>(ур. ${lvl}, строится, осталось <span class='timer' data-left='${b.time_left}'>...</span>)</span><br><span style='font-size:0.95em;color:#aaa;'>+${nextIncome} ${meta.income}/мин</span>`;
                } else {
                    nextCost = {};
                    for(const k in meta.baseCost) nextCost[k] = meta.baseCost[k]*Math.pow(2,lvl-1);
                    return `${meta.icon} ${meta.name} <span style='color:#4caf50;'>(ур. ${lvl}, работает)</span><br><span style='font-size:0.95em;color:#aaa;'>+${nextIncome} ${meta.income}/мин</span><br><button class='upgrade_building' data-type='${b.type}' style='margin-top:6px;padding:5px 18px;border-radius:7px;border:none;background:#4caf50;color:#fff;cursor:pointer;font-size:1em;'>Улучшить за ${costToStr(nextCost)}, ${nextTime} мин</button>`;
                }
            }).join('<hr style="border:none;border-top:1px solid #eee;margin:7px 0;">');
            document.querySelectorAll('.upgrade_building').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('modal_bg').style.display = 'none';
                    const type = btn.getAttribute('data-type');
                    fetch('buildings.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '', action: BUILDING_META[type].build })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.error) { alert(data.error); return; }
                        if(data.resources) updateResourcesUI(data.resources);
                        if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
                    });
                };
            });
        }
        function startBuildingsTimer() {
            if(g_buildings_timer) clearInterval(g_buildings_timer);
            g_buildings_timer = setInterval(() => {
                document.querySelectorAll('.timer').forEach(span => {
                    let left = parseInt(span.getAttribute('data-left'));
                    if(left>0) {
                        left--;
                        span.setAttribute('data-left', left);
                        const min = Math.floor(left/60);
                        const sec = left%60;
                        span.textContent = min+':'+(sec<10?'0':'')+sec;
                    } else {
                        span.textContent = '0:00';
                    }
                });
            }, 1000);
        }
        function fetchBuildingsAndResources() {
            fetch('buildings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '' })
            })
            .then(r => r.json())
            .then(data => {
                if(data.error) { alert(data.error); return; }
                if(data.resources) updateResourcesUI(data.resources);
                if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
                if((!data.buildings || !data.buildings.length) && !data.error) document.getElementById('buildings_content').textContent = 'Нет построек';
            });
        }
        (function() {
            var user = (window.DemoApp && DemoApp.initDataUnsafe.user) || (Telegram.WebApp.initDataUnsafe && Telegram.WebApp.initDataUnsafe.user);
            g_user = user;
            var welcome = '';
            var welcomeElement = document.getElementById('welcome_user');
            
            if (user) {
                var username = user.first_name || user.username;
                if (username) {
                    welcome = 'Добро пожаловать, ';
                    welcomeElement.innerHTML = welcome + '<a href="profile.php?user_id=' + user.id + '" style="color: #2678b6; text-decoration: none; cursor: pointer; border-bottom: 1px solid transparent; transition: border-bottom-color 0.2s;">' + username + '</a>';
                } else {
                    welcome = 'Добро пожаловать!';
                    welcomeElement.textContent = welcome;
                }
            } else {
                welcome = 'Добро пожаловать!';
                welcomeElement.textContent = welcome;
            }
            
            fetchBuildingsAndResources();
        })();
        document.getElementById('build_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'flex';
            // Обновить стоимости
            for(const type in BUILDING_META) {
                const lvl = getBuildingLevel(type);
                const cost = getBuildingCost(type);
                const time = getBuildingTime(type);
                const income = getBuildingIncome(type);
                document.getElementById(type+'_cost').textContent = `${costToStr(cost)}, ${time} мин, +${income} ${BUILDING_META[type].income}/мин`;
            }
        };
        document.getElementById('close_modal_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'none';
        };
        document.getElementById('lumbermill_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'none';
            fetch('buildings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '', action: 'build_lumbermill' })
            })
            .then(r => r.json())
            .then(data => {
                if(data.error) { alert(data.error); return; }
                if(data.resources) updateResourcesUI(data.resources);
                if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
            });
        };
        document.getElementById('farm_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'none';
            fetch('buildings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '', action: 'build_farm' })
            })
            .then(r => r.json())
            .then(data => {
                if(data.error) { alert(data.error); return; }
                if(data.resources) updateResourcesUI(data.resources);
                if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
            });
        };
        document.getElementById('ironmine_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'none';
            fetch('buildings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '', action: 'build_ironmine' })
            })
            .then(r => r.json())
            .then(data => {
                if(data.error) { alert(data.error); return; }
                if(data.resources) updateResourcesUI(data.resources);
                if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
            });
        };
        document.getElementById('silvermine_btn').onclick = function() {
            document.getElementById('modal_bg').style.display = 'none';
            fetch('buildings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: g_user.id, username: g_user.username || '', first_name: g_user.first_name || '', action: 'build_silvermine' })
            })
            .then(r => r.json())
            .then(data => {
                if(data.error) { alert(data.error); return; }
                if(data.resources) updateResourcesUI(data.resources);
                if(data.buildings) { g_buildings = data.buildings; updateBuildingsUI(); startBuildingsTimer(); }
            });
        };
        document.getElementById('chat_btn').onclick = function() {
            window.location.href = 'chat.php';
        };
    </script>
</body>
</html>