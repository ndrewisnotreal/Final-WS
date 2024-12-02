<?php
require_once realpath(__DIR__ . '/.') . "/vendor/autoload.php";
require_once __DIR__ . "/html_tag_helpers.php";

// Setup some additional prefixes for DBpedia
\EasyRdf\RdfNamespace::set('dbc', 'http://dbpedia.org/resource/Category:');
\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbpedia', 'http://dbpedia.org/property/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('gold', 'http://purl.org/linguistics/gold/');
\EasyRdf\RdfNamespace::set('dbp', 'http://dbpedia.org/property/');

$sparql = new \EasyRdf\Sparql\Client('http://dbpedia.org/sparql');

// Query for total number of mountains
$all_mount = $sparql->query("
    SELECT (COUNT(DISTINCT ?mountain) AS ?res)
    WHERE {
        ?mountain rdf:type dbo:Mountain .
        ?mountain dbo:abstract ?desc .
        ?mountain dbp:country ?country .
        ?mountain dbp:name ?name .
        FILTER (langMatches(lang(?desc), 'EN')) .
        FILTER (langMatches(lang(?name), 'EN')) .
    }
");

foreach ($all_mount as $res) {
    $all_mount = $res->res;
}

$indo_mount = $sparql->query("
    SELECT (COUNT(DISTINCT ?mountain) AS ?res)
    WHERE {
        ?mountain rdf:type dbo:Mountain .
        ?mountain dbo:abstract ?desc .
        ?mountain dbp:country dbr:Indonesia .
        FILTER (langMatches(lang(?desc), 'EN')) .
    }
");

foreach ($indo_mount as $res) {
    $indo_mount = $res->res;
}

// Handle search query
$result = "";
if (isset($_GET['country'])) {
    $getCountry = $_GET['country'];
    $getCountry = str_replace(' ', '_', ucwords($getCountry));
    $result = $sparql->query(
        'SELECT DISTINCT ?country ?nama ?mountain ?mountainName WHERE {' .
        '  ?mountain rdf:type dbo:Mountain;' .
        '  dbo:abstract ?desc;' .
        '  dbp:name ?nama;' .
        '  dbp:country dbr:' . $getCountry . ' .' .
        '  dbr:' . $getCountry . ' dbp:commonName ?country .' .
        '  FILTER langMatches(lang(?desc), "EN")' .
        '  FILTER langMatches(lang(?nama), "EN")' .
        '  FILTER langMatches(lang(?country), "EN")' .
        '} ORDER BY ?nama'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1.0" />
    <title>MountSearch</title>
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
            <h1 class="text-4xl font-bold mb-6 text-teal-600">Welcome to MountSearch</h1>
            
            <!-- Search Form -->
            <form method="GET" class="flex justify-center items-center mb-8">
                <input name="country" placeholder="Search Country" class="w-full md:w-1/2 p-4 border border-gray-300 rounded-l-full focus:outline-none focus:ring-2 focus:ring-teal-600 shadow-md" type="text" />
                <button type="submit" class="bg-teal-600 text-white px-6 py-4 rounded-r-full hover:bg-teal-700 transition duration-300 shadow-md">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </main>
    
    <?php if ($result): ?>
    <section class="bg-gray-100 py-12">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-teal-600">Search Results</h2>
            <div class="overflow-x-auto">
                <table class="w-full bg-white shadow-md rounded-lg">
                    <thead class="bg-teal-600 text-white">
                        <tr>
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Country</th>
                            <th class="py-3 px-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id = 1;
                        foreach ($result as $row) :
                            if ($row->nama != "") :
                        ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="py-3 px-4"><?= $id ?></td>
                            <td class="py-3 px-4"><?= $row->nama ?></td>
                            <td class="py-3 px-4"><?= $row->country ?></td>
                            <td class="py-3 px-4">
                                <form method="POST" action="./details.php">
                                    <input type="hidden" name="mountain_name" value="<?= str_replace('http://dbpedia.org/resource/', '', $row->mountain) ?>" />
                                    <input type="hidden" name="country_name" value="<?= $row->country ?>" />
                                    <button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-300">Details</button>
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
            <h2 class="text-3xl font-bold mb-4 text-teal-600">How many data do we have in MountSearch?</h2>
            <div class="bg -white shadow-md rounded-lg p-6">
                <p class="text-xl">Total Mountains: <strong><?= $all_mount ?></strong></p>
                <p class="text-xl">Mountains in Indonesia: <strong><?= $indo_mount ?></strong></p>
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