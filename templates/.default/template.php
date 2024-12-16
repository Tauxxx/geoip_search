<div class="container">
    <div class="row">
        <div class="col">
            <h1>Проверка IP</h1>
            <form id="geoip-form">
                <input type="text" name="ip" id="geoip-input" placeholder="Введите IP-адрес" required>
                <button type="submit">Поиск</button>
            </form>
            <div id="geoip-result"></div>
        </div>
    </div>
</div>

<script>
    document.getElementById('geoip-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const resultDiv = document.getElementById('geoip-result');

        const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        const ip = document.getElementById('geoip-input').value;

        if (!ipRegex.test(ip)) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Введите корректный IP-адрес.</div>';
            return;
        }

        BX.ajax.runComponentAction('tau:geoip_search', 'getGeoData', {
            mode: 'class',
            data: {
                ip: ip
            }
        }).then(function(response) {
            if (response.status === 'success') {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">Ошибка: ' + response.errors[0].message + '</div>';
            }
        }).catch(function(error) {
            console.error(error);
            document.getElementById('geoip-result').innerHTML = '<div class="alert alert-danger">Произошла ошибка</div>';
        });
    });
</script>