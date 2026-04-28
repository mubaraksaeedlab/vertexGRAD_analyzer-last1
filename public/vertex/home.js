(function () {
    const canvas = document.getElementById("bg3d");

    if (!canvas) {
        return;
    }

    if (typeof THREE === "undefined") {
        console.error("Three.js is not loaded.");
        return;
    }

    const scene = new THREE.Scene();

    const camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        1000
    );

    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        alpha: true,
        antialias: true,
    });

    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    camera.position.z = 5;

    const nodes = [];
    const nodeCount = 40;

    for (let i = 0; i < nodeCount; i++) {
        const geometry = new THREE.SphereGeometry(0.05, 16, 16);
        const material = new THREE.MeshBasicMaterial({
            color: 0x1a968f,
        });

        const sphere = new THREE.Mesh(geometry, material);

        sphere.position.x = (Math.random() - 0.5) * 6;
        sphere.position.y = (Math.random() - 0.5) * 6;
        sphere.position.z = (Math.random() - 0.5) * 4;

        sphere.userData.speedX = (Math.random() - 0.5) * 0.002;
        sphere.userData.speedY = (Math.random() - 0.5) * 0.002;

        scene.add(sphere);
        nodes.push(sphere);
    }

    const lineMaterial = new THREE.LineBasicMaterial({
        color: 0x0ea5e9,
        transparent: true,
        opacity: 0.35,
    });

    const lines = [];

    for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const distance = nodes[i].position.distanceTo(nodes[j].position);

            if (distance < 1.8 && Math.random() < 0.14) {
                const points = [
                    nodes[i].position.clone(),
                    nodes[j].position.clone(),
                ];

                const geometry = new THREE.BufferGeometry().setFromPoints(points);
                const line = new THREE.Line(geometry, lineMaterial);

                line.userData.from = nodes[i];
                line.userData.to = nodes[j];

                scene.add(line);
                lines.push(line);
            }
        }
    }

    function updateNodes() {
        nodes.forEach((node) => {
            node.position.x += node.userData.speedX;
            node.position.y += node.userData.speedY;

            if (node.position.x > 3 || node.position.x < -3) {
                node.userData.speedX *= -1;
            }

            if (node.position.y > 3 || node.position.y < -3) {
                node.userData.speedY *= -1;
            }
        });
    }

    function updateLines() {
        lines.forEach((line) => {
            const positions = line.geometry.attributes.position.array;

            positions[0] = line.userData.from.position.x;
            positions[1] = line.userData.from.position.y;
            positions[2] = line.userData.from.position.z;

            positions[3] = line.userData.to.position.x;
            positions[4] = line.userData.to.position.y;
            positions[5] = line.userData.to.position.z;

            line.geometry.attributes.position.needsUpdate = true;
        });
    }

    function animate() {
        requestAnimationFrame(animate);

        updateNodes();
        updateLines();

        scene.rotation.y += 0.0008;
        scene.rotation.x += 0.0002;

        renderer.render(scene, camera);
    }

    animate();

    window.addEventListener("resize", function () {
        const width = window.innerWidth;
        const height = window.innerHeight;

        renderer.setSize(width, height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    });

    window.startExperience = function () {
        window.scrollTo({
            top: window.innerHeight,
            behavior: "smooth",
        });
    };
})();