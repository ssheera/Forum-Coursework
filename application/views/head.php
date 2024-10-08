<?php $title = isset($title) ? $title : 'Home'; ?>
<head>
	<title><?php echo $title?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<!-- Hind Font -->
	<link href="https://fonts.googleapis.com/css?family=Hind:wght@100,200,300,400,600,700" rel="stylesheet">

	<!-- JQuery -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<!-- Bootstrap JS -->
	<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<!-- Font Awesome -->
	<script src="https://kit.fontawesome.com/c69c998a65.js" crossorigin="anonymous"></script>
	<!-- React -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
	<!-- React DOM -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
	<!-- Babel -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.24.4/babel.min.js"></script>

	<script>

		// function map the segments in the url, like the PHP function
		// made it here since it shouldn't really be done server-side, rather client-side
		// less reliance on the server
		function get_segments() {
			const path = window.location;
			// split the pathname into segments
			const segments = path.pathname.split('/');
			const mapped = {};
			// start at 0 if the length is odd, otherwise start at 1
			// if first bit is 1, that means that it is odd
			for (let i = segments.length & 1; i < segments.length; i += 2) {
				mapped[segments[i].toLowerCase()] = segments[i + 1];
			}
			return mapped;
		}
		// globally accessible uri variable
		const uri = get_segments();
	</script>

	<style>
		body {
			color: #090909;
			font-family: 'Hind', sans-serif;
			background: #FAFAFA;
			--bs-dark-rgb = #090909;
			display: flex;
			flex-direction: column;
		}
		.nav-link.text-dark:hover {
			color: #ed8106 !important;
		}
		.text-theme {
			color: #ed8106;
		}
		.bg-theme {
			background: #ed8106;
		}
		.btn-theme {
			--bs-btn-color: #fff;
			--bs-btn-bg: #ed8106;
			--bs-btn-border-color: #ed8106;
			--bs-btn-hover-color: #fff;
			--bs-btn-hover-bg: #d46d09;
			--bs-btn-hover-border-color: #d46d09;
			--bs-btn-focus-shadow-rgb: 60, 153, 110;
			--bs-btn-active-color: #fff;
			--bs-btn-active-bg: #c85a09;
			--bs-btn-active-border-color: #c85a09;
			--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
			--bs-btn-disabled-color: #fff;
			--bs-btn-disabled-bg: #ed8106;
			--bs-btn-disabled-border-color: #ed8106;
		}
		.border-theme {
			border-color: #ed8106;
		}
	</style>
</head>
