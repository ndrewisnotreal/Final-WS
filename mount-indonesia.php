<?php

require_once realpath(__DIR__ . '/.') . "/vendor/autoload.php";
require_once __DIR__ . "/html_tag_helpers.php";

// Setup some additional prefixes for DBpedia
\EasyRdf\RdfNamespace::set('dbc', 'http://dbpedia.org/resource/Category:');
\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbpedia', 'http://dbpedia.org/property/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');

$sparql = new \EasyRdf\Sparql\Client('http://dbpedia.org/sparql');
$sparql_jena = new \EasyRdf\Sparql\Client('http://localhost:3030/Mountain/sparql');

// Query for mountains in Indonesia
$q = 'SELECT DISTINCT ?m ?name ?country WHERE {
    ?m rdf:type dbo:Mountain;
       rdfs:label ?name;
       dbo:country dbr:Indonesia.
    FILTER langMatches(lang(?name), "EN") .
}';

$result = $sparql_jena->query($q);

// You can define 'country' as a static value since all results are from Indonesia
$country = "Indonesia";

// Query for total number of mountains
$all_mountains = $sparql->query(
    'SELECT (COUNT(DISTINCT ?m) AS ?res) WHERE {
        ?m rdf:type dbo:Mountain .
    }'
);

foreach ($all_mountains as $res) {
    $total_mountains = $res->res;
}

// Query for total number of Indonesian mountains
$indo_mountains = $sparql->query(
    'SELECT (COUNT(DISTINCT ?m) AS ?res) WHERE {
        ?m rdf:type dbo:Mountain .
        ?m dbo:country dbr:Indonesia .
    }'
);

foreach ($indo_mountains as $res) {
    $total_indo_mountains = $res->res;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1.0" />
    <title>MountSearch - Pencarian Gunung di Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto flex justify-between items-center py-4 px-6">
            <div class="flex items-center">
                <span class="text-2xl font-bold text-yellow-500 ml-2">MountSearch</span>
            </div>
            <nav class="flex items-center space-x-4">
                <button class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition duration-300">Search</button>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="bg-gray-100 py-12">
        <div class="container mx-auto text-center">
            <h1 class="text-4xl font-bold mb-6 text-teal-600">Selamat Datang di MountSearch</h1>
            
            <!-- Search Form -->
            <form method="GET" class="flex justify-center items-center mb-8">
                <input name="country" placeholder="Cari Gunung di Indonesia" class="w-full md:w-1/2 p-4 border border-gray-300 rounded-l-full focus:outline-none focus:ring -2 focus:ring-teal-600 shadow-md" type="text" />
                <button type="submit" class="bg-teal-600 text-white px-6 py-4 rounded-r-full hover:bg-teal-700 transition duration-300 shadow-md">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </main>
    
    <?php if ($result): ?>
    <section class="bg-gray-100 py-12">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-te al-600">Hasil Pencarian</h2>
            <div class="overflow-x-auto">
                <table class="w-full bg-white shadow-md rounded-lg">
                    <thead class="bg-teal-600 text-white">
                        <tr>
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Nama Gunung</th>
                            <th class="py-3 px-4">Negara</th>
                            <th class="py-3 px-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id = 1;
                        foreach ($result as $row) :
                            if ($row->name != "") :
                        ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="py-3 px-4"><?= $id ?></td>
                            <td class="py-3 px-4"><?= $row->name ?></td>
                            <td class="py-3 px-4"><?= $country ?></td>
                            <td class="py-3 px-4">
                                <form method="POST" action="./details.php">
                                    <input type="hidden" name="mountain_name" value="<?= str_replace('http://dbpedia.org/resource/', '', $row->m) ?>" />
                                    <input type="hidden" name="country_name" value="<?= $country ?>" />
                                    <button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-300">Detail</button>
                                </form>
                            </td>
                        </tr>
                        <?php $id++; ?>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Statistics Section -->
    <section class="bg-white py-12">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-teal-600">Berapa banyak data yang kita miliki di MountSearch?</h2>
            <div class="bg-white shadow-md rounded-lg p-6">
                <p class="text-xl">Total Gunung: <strong><?= $total_mountains ?></strong></p>
                <p class="text-xl">Gunung di Indonesia: <strong><?= $total_indo_mountains ?></strong></p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2023 MountSearch. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>