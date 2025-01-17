<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $grid = $data['grid'];
    $start = $data['start'];
    $end = $data['end'];
    $saveResult = $data['saveResult'];

    function aStar($grid, $start, $end) {
        $openList = [];
        $closedList = [];

        $openList[] = ["position" => $start, "g" => 0, "h" => heuristic($start, $end), "parent" => null];
        $visited = [];

        while (!empty($openList)) {
            usort($openList, function($a, $b) {
                return ($a['g'] + $a['h']) <=> ($b['g'] + $b['h']);
            });

            $currentNode = array_shift($openList);
            $closedList[] = $currentNode;
            $visited[] = $currentNode['position'];

            if ($currentNode['position'] === $end) {
                return [
                    "path" => reconstructPath($currentNode),
                    "visited" => $visited
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

    /*
    --- Эвристика: расчет расстояния ---
    (формула √((x2-x1)² + (y2-y1)²)).
    */
    function heuristic($pos, $goal) {
        return sqrt(pow($pos[0] - $goal[0], 2) + pow($pos[1] - $goal[1], 2));
    }

    
    function getNeighbors($position, $grid) {
        $neighbors = [];
        $directions = [[0, -1], [-1, 0], [0, 1], [1, 0]];

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

    
    $result = aStar($grid, $start, $end);

    if ($result) {
        $path = $result['path'];

        foreach ($path as [$x, $y]) {
            $grid[$x][$y] = 3;
        }

        $content = "Grid:\n";
        foreach ($grid as $row) {
            $content .= implode(" ", $row) . "\n";
        }

        if ($saveResult) {
            $filename = "astar_result.txt";
            file_put_contents($filename, $content);

            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . $filename);
            readfile($filename);
            unlink($filename);
            exit;
        } else {
            echo json_encode($result);
            exit;
        }
    } else {
        echo json_encode(["error" => "No path found"]);
        exit;
    }
}
