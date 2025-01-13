<?php
// Проверяем, что запрос выполнен методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* 
    --- Получение данных из входного запроса ---
    - Извлекаются данные из тела HTTP POST-запроса.
    - JSON преобразуется в PHP массив.
    - Данные включают:
      - $grid: двумерная сетка, где 0 - свободная клетка, а 1 - препятствие.
      - $start: начальная точка [x, y].
      - $end: конечная точка [x, y].
      - $saveResult: флаг (true/false), нужно ли сохранить результат в файл.
    */
    $data = json_decode(file_get_contents('php://input'), true);
    $grid = $data['grid'];
    $start = $data['start'];
    $end = $data['end'];
    $saveResult = $data['saveResult'];

    /*
    --- Основная функция: поиск пути алгоритмом A* ---
    - Используется для нахождения кратчайшего пути на сетке.
    - Включает:
      - $openList: список узлов, которые нужно исследовать.
      - $closedList: список узлов, которые уже были исследованы.
    - Для каждого узла вычисляются:
      - g: стоимость пути от начальной точки.
      - h: эвристическая оценка расстояния до цели.
    - Возвращает:
      - Путь, если он найден (массив позиций).
      - Список всех посещенных узлов (для визуализации).
    */
    function aStar($grid, $start, $end) {
        $openList = [];
        $closedList = [];

        // Добавляем начальный узел в список открытых узлов
        $openList[] = ["position" => $start, "g" => 0, "h" => heuristic($start, $end), "parent" => null];
        $visited = [];

        while (!empty($openList)) {
            // Сортируем открытый список по стоимости (g + h)
            usort($openList, function($a, $b) {
                return ($a['g'] + $a['h']) <=> ($b['g'] + $b['h']);
            });

            // Извлекаем узел с минимальной стоимостью
            $currentNode = array_shift($openList);
            $closedList[] = $currentNode;
            $visited[] = $currentNode['position'];

            // Проверяем, достигнута ли цель
            if ($currentNode['position'] === $end) {
                return [
                    "path" => reconstructPath($currentNode),
                    "visited" => $visited
                ];
            }

            // Получаем всех соседей текущего узла
            foreach (getNeighbors($currentNode['position'], $grid) as $neighbor) {
                $g = $currentNode['g'] + 1;
                $h = heuristic($neighbor, $end);

                // Пропускаем узлы, уже исследованные или менее оптимальные
                if (inClosedList($neighbor, $closedList)) {
                    continue;
                }

                $existingNode = findInOpenList($neighbor, $openList);
                if ($existingNode && $g >= $existingNode['g']) {
                    continue;
                }

                // Добавляем узел в открытый список
                $openList[] = ["position" => $neighbor, "g" => $g, "h" => $h, "parent" => $currentNode];
            }
        }

        // Если путь не найден, возвращаем null
        return null;
    }

    /*
    --- Эвристика: расчет расстояния ---
    - Вычисляет расстояние от текущей точки до цели.
    - Используется евклидово расстояние (формула √((x2-x1)² + (y2-y1)²)).
    */
    function heuristic($pos, $goal) {
        return sqrt(pow($pos[0] - $goal[0], 2) + pow($pos[1] - $goal[1], 2));
    }

    /*
    --- Получение соседей текущей позиции ---
    - Возвращает массив доступных соседних клеток.
    - Проверяются все 4 направления (вверх, вниз, влево, вправо).
    - Сосед добавляется, если он находится в пределах сетки и не является препятствием.
    */
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

    /*
    --- Проверка, находится ли узел в закрытом списке ---
    - Проходит по всем узлам из closedList и проверяет их позиции.
    - Возвращает true, если узел уже в закрытом списке, иначе false.
    */
    function inClosedList($position, $closedList) {
        foreach ($closedList as $node) {
            if ($node['position'] === $position) {
                return true;
            }
        }
        return false;
    }

    /*
    --- Поиск узла в открытом списке ---
    - Проверяет, есть ли указанный узел в openList.
    - Возвращает узел, если он найден, или null, если его нет.
    */
    function findInOpenList($position, $openList) {
        foreach ($openList as $node) {
            if ($node['position'] === $position) {
                return $node;
            }
        }
        return null;
    }

    /*
    --- Восстановление пути ---
    - Восстанавливает путь от конечной точки до начальной.
    - Двигается от текущего узла через `parent`, пока не достигнет стартового узла.
    - Возвращает путь в виде массива позиций.
    */
    function reconstructPath($node) {
        $path = [];
        while ($node) {
            $path[] = $node['position'];
            $node = $node['parent'];
        }
        return array_reverse($path);
    }

    /*
    --- Запуск поиска пути ---
    - Вызывает функцию aStar() с параметрами.
    - Если путь найден:
      - Обновляет сетку: путь помечается числом 3.
      - Формирует содержимое для сохранения или вывода.
    - Если путь не найден, возвращает ошибку.
    */
    $result = aStar($grid, $start, $end);

    if ($result) {
        $path = $result['path'];

        // Обновляем сетку: помечаем путь числом 3
        foreach ($path as [$x, $y]) {
            $grid[$x][$y] = 3;
        }

        // Формируем строку для сохранения
        $content = "Grid:\n";
        foreach ($grid as $row) {
            $content .= implode(" ", $row) . "\n";
        }

        // Сохраняем результат в файл или возвращаем в ответ
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
