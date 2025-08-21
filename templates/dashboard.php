<div class="card">
    <div class="card-header">
        <h3 class="card-title" data-i18n="dashboard">Dashboard</h3>
    </div>
    <div class="card-body">
        <p data-i18n="dashboard_welcome">Welcome to the Workship Framework dashboard!</p>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Stats</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                New Orders
                                <span class="badge bg-primary rounded-pill">14</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Active Users
                                <span class="badge bg-success rounded-pill">257</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pending Issues
                                <span class="badge bg-warning rounded-pill">3</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Server Status</h5>
                        <p>All systems are running smoothly.</p>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">Operational</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title">Monthly Report</h5>
            </div>
            <div class="card-body">
                <canvas id="reportChart"></canvas>
            </div>
        </div>
    </div>
</div>
