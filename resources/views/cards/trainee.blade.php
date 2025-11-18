<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        @vite('resources/css/app.css')

        <style>
            /* Page wrapper — A4 */
            .page {
                width: 210mm;
                height: 297mm;
                padding: 5mm 0;
                display: flex;
                flex-direction: column;
                justify-content: space-around;
                align-items: center;
                page-break-after: auto;
            }

            .page:not(:last-child) {
                page-break-after: always;
            }


            /* Full-size Card for hand printing */
            .gym-card {
                width: 550px;
                /* bigger for hand card */
                background: #0d0d0f;
                color: #ffffff;
                border-radius: 20px;
                padding: 20px 25px;
                /* bigger padding */
                font-family: "Segoe UI", Arial, sans-serif;
                display: flex;
                flex-direction: column;
                gap: 24px;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.40);
                aspect-ratio: auto;
                /* fit content height */
                margin: 10px 0;
                /* reduce vertical spacing */

                page-break-inside: avoid !important;
                break-inside: avoid !important;

                /* browser fallbacks */
                -webkit-column-break-inside: avoid !important;
                -webkit-region-break-inside: avoid !important;
                -webkit-page-break-inside: avoid !important;
            }

            /* Top Section */
            .card-header {
                display: flex;
                align-items: center;
                gap: 20px;
                /* bigger spacing */
            }

            .logo {
                width: 45px;
                /* bigger logo */
                height: 45px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 28px;
                /* bigger logo text */
                font-weight: bold;
            }

            .card-header h1 {
                font-size: 26px;
                /* bigger title text */
                letter-spacing: 1.5px;
                font-weight: 700;
                margin: 0;
            }

            /* Middle Section */
            .card-body {
                display: flex;
                gap: 30px;
                /* bigger gap */
            }

            .member-photo {
                width: 150px;
                /* bigger photo */
                height: 160px;
                object-fit: cover;
                border-radius: 14px;
                border: 4px solid #d50000;
            }

            .member-info {
                display: flex;
                flex-direction: column;
                gap: 12px;
                width: calc(100% - 180px);
            }

            .info-block label {
                font-size: 14px;
                /* bigger labels */
                text-transform: uppercase;
                color: #bbbbbb;
            }

            .info-block p {
                margin: 0;
                font-size: 19px;
                /* bigger content text */
                font-weight: 700;
            }

            /* QR Code */
            .qr-code {
                width: 120px;
                /* bigger QR code */
                height: 120px;
            }
        </style>



    </head>

    <body>

        @foreach ($trainees->chunk(2) as $traineeChunk)
            <div class="page">
                @foreach ($traineeChunk as $trainee)
                    <div class="gym-card">
                        <div class="card-header">
                            <div class="logo bg-white">
                                <img src="{{ asset('images/bitmap1.png') }}" alt="">
                            </div>
                            <div>
                                <h2 class="text-gym-text-primary text-xl font-bold">AMAL GYM</h2>
                                <p class="text-gym-text-secondary text-sm font-medium">OUARZAZATE</p>
                            </div>
                        </div>

                        <div class="card-body">
                            <img src="{{ url($trainee->photo_url) }}" class="member-photo" />

                            <div class="member-info">
                                <div class="info-block">
                                    <h3 class="text-gym-text-primary text-2xl font-bold">
                                        {{ $trainee->full_name }}
                                    </h3>
                                </div>

                                <div class="info-block">
                                    <label>Membership Type</label>
                                    <p>Monthly Membership</p>
                                </div>

                                <div class="info-block flex justify-between">
                                    <div>
                                        <label>Member ID</label>
                                        <p>ID-{{ str_pad($trainee->id, 5, '0', STR_PAD_LEFT) }}</p>
                                    </div>

                                    <div>
                                        @php
                                            $qr = tbQuar\Facades\Quar::eye('rounded')
                                                ->color(235, 12, 83)
                                                ->backgroundColor(13, 13, 15)
                                                ->generate('Member: ' . $trainee->id);
                                        @endphp
                                        {!! $qr !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach


    </body>

</html>
