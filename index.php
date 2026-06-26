<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Santo Tomás Apóstol - Gestión Parroquial</title>
    
    <link rel="stylesheet" href="recursos/fontawesome/css/all.min.css">
    
    <style>
        /* VARIABLES DE COLOR */
        :root {
            --primario-oscuro: #2C1E16; 
            --primario-medio: #5C4033; 
            --acento-dorado: #C69C6D; 
            --dorado-brillante: #E5B880; 
            --fondo-principal: #F4F1EA; 
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }

        body { 
            background-color: var(--fondo-principal); 
            color: var(--primario-oscuro); 
            overflow-x: hidden; 
        }

        /* BARRA DE NAVEGACIÓN SUPERIOR */
        header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 30px 50px;
            background-color: transparent; 
            position: absolute;
            width: 100%;
            top: 0;
            z-index: 100;
        }

        .logo { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: var(--dorado-brillante);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }
        
        .logo i { font-size: 2.2rem; }

        /* SECCIÓN PRINCIPAL */
        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(rgba(44, 30, 22, 0.8), rgba(44, 30, 22, 0.9)), url('recursos/img/fondo-catedral (1).jpg') center/cover no-repeat;
            color: #fff;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            color: var(--dorado-brillante);
            margin-bottom: 5px;
            text-shadow: 2px 4px 8px rgba(0,0,0,0.6);
            max-width: 900px;
            line-height: 1.1;
        }

        .hero h2 {
            font-size: clamp(0.9rem, 1.5vw, 1.2rem);
            color: var(--acento-dorado);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 30px;
            font-weight: 600;
            opacity: 0.9;
        }

        .hero p {
            font-size: clamp(1.4rem, 3vw, 2.2rem);
            margin-bottom: 50px;
            max-width: 1000px;
            line-height: 1.5;
            color: #FFF;
            font-weight: 500;
            font-style: italic;
            text-shadow: 1px 2px 5px rgba(0,0,0,0.8);
            min-height: 130px;
            
            transition: opacity 0.8s ease-in-out;
        }

        .ocultar-texto {
            opacity: 0 !important;
        }

        /* BOTÓN DE INGRESO */
        .btn-cta {
            background: linear-gradient(135deg, var(--acento-dorado), #a37c52);
            color: var(--primario-oscuro);
            padding: 18px 45px;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 50px; 
            box-shadow: 0 8px 25px rgba(198, 156, 109, 0.4);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .btn-cta:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 30px rgba(198, 156, 109, 0.6); 
            background: linear-gradient(135deg, var(--dorado-brillante), var(--acento-dorado));
        }

        /* Responsivo */
        @media (max-width: 768px) {
            header { padding: 20px; justify-content: center; }
            .logo span { font-size: 1.2rem; } 
            .hero { padding: 0 20px; }
            .hero p { min-height: 180px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <i class="fas fa-church"></i>
            <span>Catedral Metropolitana.</span>
        </div>
    </header>

    <section class="hero">
        <i class="fas fa-cross" style="font-size: 4rem; color: var(--acento-dorado); margin-bottom: 20px; opacity: 0.8;"></i>
        
        <h1>Santo Tomás Apóstol</h1>
        <h2>Sistema de Gestión Parroquial</h2>
        
        <p id="frase-dinamica">«La red digital puede ser un lugar rico en humanidad, no una red de cables, sino de personas humanas.» — Papa Francisco</p>
        
        <a href="vistas/login.php" class="btn-cta">
            Ingresar al Portal <i class="fas fa-arrow-right"></i>
        </a>
    </section>

    <script>

        //Frases religiosas
        const frases = [
            "«La red digital puede ser un lugar rico en humanidad, no una red de cables, sino de personas humanas.» — Papa Francisco",
            "«Que la tecnología sirva para construir puentes de entendimiento, caridad y paz.» — San Juan Pablo II",
            "«El desarrollo tecnológico debe ir siempre acompañado de un auténtico desarrollo espiritual y moral.» — Benedicto XVI",
            "«Pongamos las herramientas de la innovación al servicio del prójimo y de la obra creadora de Dios.»"
        ];

        let indiceActual = 0;
        const elementoFrase = document.getElementById('frase-dinamica');

        setInterval(() => {
            elementoFrase.classList.add('ocultar-texto');

            setTimeout(() => {
                indiceActual++;
                if (indiceActual >= frases.length) {
                    indiceActual = 0; 
                }
                
                elementoFrase.textContent = frases[indiceActual];
                elementoFrase.classList.remove('ocultar-texto');
            }, 800); 

        }, 7000); 
    </script>
</body>
</html>