<?php
function aStar($grid, $start, $end) {
    $openList = [];
    $closedList = [];
    $counter = 0;

    $openList[] = ["position" => $start, "g" => 0, "h" => heuristic($start, $end), "parent" => null];
    $visited = [];

    while (!empty($openList)) {
        
        usort($openList, function($a, $b) {
            return ($a['g'] + $a['h']) <=> ($b['g'] + $b['h']);
        });
        $currentNode = array_shift($openList);
        $closedList[] = $currentNode;
        $visited[] = $currentNode['position'];
        $counter++;
        if ($currentNode['position'] === $end) {
            return [
                "path" => reconstructPath($currentNode),
                "visited" => $visited,
                "steps" => $counter
            ];
        }

        foreach (getNeighbors($currentNode['position'], $grid) as $neighbor) {
            $g = $currentNode['g'] + 1;
            $h = heuristic($neighbor, $end);

            if (inClosedList($neighbor, $closedList)) {
                continue;
            }

            $existingNode = findInOpenList($neighbor, $openList);
            if ($existingNode && $g >= $existingNode['g']) {
                continue;
            }

            $openList[] = ["position" => $neighbor, "g" => $g, "h" => $h, "parent" => $currentNode];
        }
    }

    return null;
}

function heuristic($pos, $goal) {
    return sqrt(pow($pos[0] - $goal[0], 2) + pow($pos[1] - $goal[1], 2));
}

function getNeighbors($position, $grid) {
    $neighbors = [];
    $directions = [[1, 0], [0, -1], [0, 1], [-1, 0]];

    foreach ($directions as $direction) {
        $x = $position[0] + $direction[0];
        $y = $position[1] + $direction[1];
        if (isset($grid[$x][$y]) && $grid[$x][$y] === 0) {
            $neighbors[] = [$x, $y];
        }
    }

    return $neighbors;
}

function inClosedList($position, $closedList) {
    foreach ($closedList as $node) {
        if ($node['position'] === $position) {
            return true;
        }
    }
    return false;
}

function findInOpenList($position, $openList) {
    foreach ($openList as $node) {
        if ($node['position'] === $position) {
            return $node;
        }
    }
    return null;
}

function reconstructPath($node) {
    $path = [];
    while ($node) {
        $path[] = $node['position'];
        $node = $node['parent'];
    }
    return array_reverse($path);
}

$grid = [];

$filePath = __DIR__ . '/grid2.txt';
if (!file_exists($filePath)) {
    die("file error\n");
}

$file = fopen($filePath, 'r');

while (($line = fgets($file)) !== false) {
    $grid[] = array_map('intval', explode(' ', trim($line)));
}

fclose($file);

echo "grid:\n";
foreach ($grid as $row) {
    echo implode(' ', $row) . "\n";
}

echo "start position input (y x): ";
$startInput = trim(fgets(STDIN));
$start = array_map('intval', explode(' ', $startInput));

echo "end position input (y x): ";
$endInput = trim(fgets(STDIN));
$end = array_map('intval', explode(' ', $endInput));

$result = aStar($grid, $start, $end);

if ($result) {
    $path = $result['path'];

    foreach ($path as [$x, $y]) {
        $grid[$x][$y] = 3;
    }

    echo "result grid:\n";
    foreach ($grid as $row) {
        echo implode(' ', $row) . "\n";
    }

    $content = "grid:\n";
    foreach ($grid as $row) {
        $content .= implode(" ", $row) . "\n";
    }

    echo "Steps: \n" . $result["steps"] . "\n";

    $outputFile = __DIR__ . '/astar_result.txt';
    file_put_contents($outputFile, $content);
    echo "saved astar_result.txt\n";
} else {
    echo "path not found\n";
}
