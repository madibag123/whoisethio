<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WHOIS Lookup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">WHOIS Lookup</h1>
        <div class="card shadow mt-4">
            <div class="card-body">
                <form id="whois-form" class="row g-3">
                    <div class="col-md-9">
                        <input type="text" id="domain-input" class="form-control" placeholder="Enter domain name (e.g., example.com)" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Lookup</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="results" class="card shadow mt-4 d-none">
            <div class="card-header bg-success text-white">WHOIS Data</div>
            <div class="card-body">
                <table id="whois-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="error" class="alert alert-danger mt-4 d-none"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('whois-form').addEventListener('submit', async function (event) {
            event.preventDefault();

            const domain = document.getElementById('domain-input').value;
            const resultsCard = document.getElementById('results');
            const errorAlert = document.getElementById('error');
            const tableBody = document.querySelector('#whois-table tbody');

            resultsCard.classList.add('d-none');
            errorAlert.classList.add('d-none');
            tableBody.innerHTML = ''; // Clear previous results

            try {
                const response = await fetch(`query.php?domain=${encodeURIComponent(domain)}`);
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                // Populate the table
                for (const [key, value] of Object.entries(data)) {
                    const row = document.createElement('tr');
                    const fieldCell = document.createElement('td');
                    const valueCell = document.createElement('td');

                    fieldCell.textContent = key;
                    
                    // Handle arrays
                    if (Array.isArray(value)) {
                        valueCell.textContent = value.join(', ');
                    } else {
                        valueCell.textContent = value;
                    }

                    row.appendChild(fieldCell);
                    row.appendChild(valueCell);
                    tableBody.appendChild(row);
                }

                resultsCard.classList.remove('d-none');
            } catch (error) {
                errorAlert.textContent = error.message;
                errorAlert.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
