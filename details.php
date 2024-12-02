<?php
require_once realpath(__DIR__ . '/.') . "/vendor/autoload.php";
require_once __DIR__ . "/html_tag_helpers.php";

\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
\EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');

$sparql = new \EasyRdf\Sparql\Client('http://dbpedia.org/sparql');

$mountain = $_POST['mountain_name'];
$mountain = str_replace(' ', '_', $mountain); // Ganti spasi dengan garis bawah

$q = 'SELECT DISTINCT ?nama ?descs ?height ?wiki ?lat ?long ?country ?image WHERE {
    dbr:' . $mountain . ' rdf:type dbo:Mountain;
    dbo:abstract ?descs;
    rdfs:label ?nama .
    OPTIONAL { dbr:' . $mountain . ' dbo:elevation ?height . }
    OPTIONAL { dbr:' . $mountain . ' foaf:isPrimaryTopicOf ?wiki . }
    OPTIONAL { dbr:' . $mountain . ' geo:lat ?lat . }
    OPTIONAL { dbr:' . $mountain . ' geo:long ?long . }
    OPTIONAL { dbr:' . $mountain . ' dbo:country ?country . }
    OPTIONAL { dbr:' . $mountain . ' dbo:thumbnail ?image . }
    FILTER langMatches(lang(?descs), "EN")
    FILTER langMatches(lang(?nama), "EN")
} LIMIT 1';

$result = $sparql->query($q);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1.0" />
    <title>Mountain Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <header class="bg-white shadow-md">
        <div class="container mx-auto flex justify-between items-center py-4 px-6">
            <span class="text-2xl font-bold text-yellow-500">Mountain Details</span>
        </div>
    </header>
    
    <main class="bg-gray-100 py-12">
        <div class="container mx-auto">
            <?php if ($result): ?>
                <?php foreach ($result as $row): ?>
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h1 class="text-4xl font-bold mb-6 text-teal-600"><?= htmlspecialchars($row->nama) ?></h1>
                            <p class="mb-4 text-gray-700 max-h-500 overflow-y-auto pr-4"><?= htmlspecialchars($row->descs) ?></p>
                            
                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <h2 class="text-2xl font-semibold mb-4 text-teal-600">Mountain Information</h2>
                                <p><strong>Height:</strong> <?= isset($row->height) ? htmlspecialchars($row->height) . ' meters' : 'N/A' ?></p>
                                <p><strong>Latitude:</strong> <?= isset($row->lat) ? htmlspecialchars($row->lat) : 'N/A' ?></p>
                                <p><strong>Longitude:</strong> <?= isset($row->long) ? htmlspecialchars($row->long) : 'N/A' ?></p>
                                
                                <?php if (isset($row->wiki)): ?>
                                    <p class="mt-4">
                                        <a href="<?= htmlspecialchars($row->wiki) ?>" target="_blank" class="text-teal-600 hover:underline">
                                            Read more on Wikipedia
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <?php if (isset($row->lat) && isset($row->long)): ?>
                                <div id="map" class="h-96 rounded-lg shadow-md"></div>
                            <?php else: ?>
                                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                                    <p class="text-gray-500">Location information not available</p>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($row->image)): ?>
                                <div class="h-96 rounded-lg shadow-md overflow-hidden">
                                    <img 
                                        src="<?= htmlspecialchars($row->image) ?>" 
                                        alt="<?= htmlspecialchars($row->nama) ?>" 
                                        class="w-full h-full object-cover object-center transition-transform duration-300 hover:scale-110"
                                    />
                                </div>
                            <?php else: ?>
                                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                                    <p class="text-gray-500">No image available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <p class="text-gray-500">No details found for this mountain.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php if (isset($row->lat) && isset($row->long)): ?>
    <script>
        // Initialize the map
        var map = L.map('map').setView([<?= htmlspecialchars($row->lat) ?>, <?= htmlspecialchars($row->long) ?>], 10);

        // Add the OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add a marker for the mountain
        L.marker([<?= htmlspecialchars($row->lat) ?>, <?= htmlspecialchars($row->long) ?>])
            .addTo(map)
            .bindPopup('<?= htmlspecialchars($row->nama) ?>')
            .openPopup();
    </script>
    <?php endif; ?>
    
    <footer class="bg-gray-800 text-white py-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2023 MountSearch. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>