<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        @vite('resources/css/app.css')
        <style>
            /* Card Container */
            .gym-card {
                width: 450px;
                background: #0d0d0f;
                color: #ffffff;
                border-radius: 18px;
                padding: 18px;
                font-family: "Segoe UI", Arial, sans-serif;
                display: flex;
                flex-direction: column;
                gap: 18px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
                aspect-ratio: 16/10;    
                margin: 30px;

                page-break-inside: avoid !important;
                break-inside: avoid !important;

                /* browser-specific fallbacks */
                -webkit-column-break-inside: avoid !important;
                -webkit-region-break-inside: avoid !important;
                -webkit-page-break-inside: avoid !important;
            }

            /* Top Section */
            .card-header {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .logo {
                width: 45px;
                height: 45px;
                /* background: #d50000; */
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                font-weight: bold;
            }

            .card-header h1 {
                font-size: 16px;
                letter-spacing: 1px;
                font-weight: 700;
            }

            /* Middle Section */
            .card-body {
                display: flex;
                gap: 15px;
            }

            .member-photo {
                width: 120px;
                height: 140px;
                object-fit: cover;
                border-radius: 10px;
                border: 2px solid #d50000;
            }

            .member-info {
                display: flex;
                flex-direction: column;
                gap: 6px;
                width: calc(100% - 120px);
            }

            /* Info Blocks */
            .info-block label {
                font-size: 10px;
                text-transform: uppercase;
                color: #bbbbbb;
            }

            .info-block p {
                margin: 0;
                font-size: 13px;
                font-weight: 600;
            }

            /* Bottom Section */
            .card-footer {
                display: flex;
                justify-content: center;
            }

            .qr-code {
                /* width: 90px;
                height: 90px; */
            }

            .break-allowed {
                page-break-inside: avoid !important;
                page-break-after: always;
            }
            /* Page Break */
            .page-break {
                page-break-after: always;
            }

        </style>
    </head>

    <body class="m-25 p-25">
        @foreach ($trainees as $trainee)
            <div class="gym-card">
                <!-- Top Section -->
                <div class="card-header">
                    <div class="logo bg-white">
                        <img src="{{ asset('images/bitmap1.png') }}" class="" alt="">
                    </div>
                    <div>
                        <h2 class="text-gym-text-primary text-lg font-bold leading-tight tracking-wide">AMAL GYM</h2>
                        <p class="text-gym-text-secondary text-xs font-medium tracking-wider">OUARZAZATE</p>
                    </div>
                </div>

                <!-- Middle Section -->
                <div class="card-body">
                    <img src="{{ url($trainee->photo_url) }}" alt="Member Photo" class="member-photo" />

                    <div class="member-info">
                        <div class="info-block">
                            {{-- <label>Full Name</label> --}}
                            <h3 id="fullName" class="text-gym-text-primary mb-2 truncate text-xl font-bold">
                                {{ $trainee->full_name }}</h3>
                        </div>

                        <div class="info-block">
                            <label>Membership Type</label>
                            <p id="membershipType">Monthly Membership</p>
                        </div>

                        <div class="info-block flex w-full justify-between">
                            <div>
                                <label>Member ID</label>
                                <p id="memberId">ID-00001</p>
                            </div>
                            <div>
                                @php
                                    $qr = tbQuar\Facades\Quar::eye('rounded')
                                        ->color(235, 12, 83)
                                        ->backgroundColor(13, 13, 15)
                                        ->generate('Quar package create qr code');

                                @endphp
                                {{ $qr }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="page-break" ></div> --}}
        @endforeach

    </body>

</html>
