<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VertexGrad</title>

    <link rel="stylesheet" href="{{ asset('vertex/home.css') }}">
</head>
<body>

    <div id="presentation-hero">
        <canvas id="bg3d"></canvas>

        <div class="hero-content">
            <span class="hero-badge">Graduation Project Presentation</span>

            <h1>VertexGrad</h1>

            <p>
                Transforming Academic Projects into Real Opportunities
            </p>

            <div class="hero-actions">
                <button type="button" onclick="startExperience()">
                    Start Experience
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="{{ asset('vertex/home.js') }}"></script>
</body>
</html>