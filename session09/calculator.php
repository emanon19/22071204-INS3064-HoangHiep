<?php
/**
 * Calculator
 * Name: Hoang Hiep
 * Student ID: 22071204
 * Date: 2026-11-10
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Never expose PHP errors to visitors.
ini_set('log_errors', '1');

session_start();

/**
 * Raised when either operand is not a valid numeric value.
 */
class InvalidInputException extends Exception
{
}

/**
 * Raised when the submitted arithmetic operator is unsupported.
 */
class InvalidOperatorException extends Exception
{
}

/**
 * Raised when division or modulo is attempted with a zero divisor.
 */
class DivisionByZeroException extends Exception
{
}

/**
 * Escape a value before placing it in HTML.
 */
function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a readable representation of a numeric result.
 */
function formatResult(float|int $result, string $operator): string
{
    if ($operator === '/') {
        return number_format((float) $result, 4, '.', '');
    }

    if (is_int($result) || (is_finite((float) $result) && floor((float) $result) === (float) $result
        && abs((float) $result) <= PHP_INT_MAX)) {
        return number_format((float) $result, 0, '.', '');
    }

    // Keep meaningful fractional digits without displaying unnecessary zeroes.
    return sprintf('%.12g', (float) $result);
}

/**
 * Append an error record to the application log.
 */
function logCalculatorError(Throwable $exception, string $expression): void
{
    $timestamp = date('Y-m-d H:i:s');
    $type = get_class($exception);
    $message = str_replace(["\r", "\n"], ' ', $exception->getMessage());
    $safeExpression = str_replace(["\r", "\n"], ' ', $expression);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $line = sprintf(
        '[%s] %s: %s | Input: "%s" | IP: %s%s',
        $timestamp,
        $type,
        $message,
        $safeExpression,
        $ip,
        PHP_EOL
    );

    // FILE_APPEND preserves earlier records. The .log file is not linked from the UI.
    error_log($line, 3, __DIR__ . '/calculator_errors.log');
}

/**
 * Perform a validated arithmetic operation.
 */
function calculate(string $firstInput, string $operator, string $secondInput): float|int
{
    if (trim($firstInput) === '' || !is_numeric(trim($firstInput))) {
        throw new InvalidInputException('The first number must be a valid numeric value. Please enter a number.');
    }

    if (trim($secondInput) === '' || !is_numeric(trim($secondInput))) {
        throw new InvalidInputException('The second number must be a valid numeric value. Please enter a number.');
    }

    $allowedOperators = ['+', '-', '*', '/', '%', '**'];
    if (!in_array($operator, $allowedOperators, true)) {
        throw new InvalidOperatorException('The selected operator is not supported. Please choose one from the list.');
    }

    $a = (float) trim($firstInput);
    $b = (float) trim($secondInput);

    if (!is_finite($a) || !is_finite($b)) {
        throw new InvalidInputException('Please enter finite numbers within PHP’s supported numeric range.');
    }

    if (($operator === '/' || $operator === '%') && $b == 0.0) {
        throw new DivisionByZeroException(
            $operator === '/'
                ? 'Division by zero is not allowed. Please enter a non-zero second number.'
                : 'Modulo by zero is not allowed. Please enter a non-zero second number.'
        );
    }

    switch ($operator) {
        case '+':
            $result = $a + $b;
            break;
        case '-':
            $result = $a - $b;
            break;
        case '*':
            $result = $a * $b;
            break;
        case '/':
            $result = $a / $b;
            break;
        case '%':
            $result = fmod($a, $b);
            break;
        case '**':
            $result = $a ** $b;
            break;
        default:
            // Defensive fallback; the operator was checked above.
            throw new InvalidOperatorException('The selected operator is not supported.');
    }

    if (is_infinite((float) $result) || is_nan((float) $result)) {
        throw new OverflowException('The calculation exceeded PHP’s supported numeric range. Try smaller numbers.');
    }

    return $result;
}

// Initialise session data and form state before rendering the page.
if (!isset($_SESSION['calculator_history']) || !is_array($_SESSION['calculator_history'])) {
    $_SESSION['calculator_history'] = [];
}

$firstInput = '';
$secondInput = '';
$operator = '+';
$notice = '';
$noticeType = '';
$expression = '';
$formattedResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) && is_string($_POST['action'])
        ? $_POST['action']
        : 'calculate';

    if ($action === 'clear_history') {
        $_SESSION['calculator_history'] = [];
        $notice = 'Calculation history has been cleared.';
        $noticeType = 'warning';
    } elseif ($action === 'calculate') {
        $firstInput = isset($_POST['first_number']) && is_scalar($_POST['first_number'])
            ? (string) $_POST['first_number']
            : '';
        $secondInput = isset($_POST['second_number']) && is_scalar($_POST['second_number'])
            ? (string) $_POST['second_number']
            : '';
        $operator = isset($_POST['operator']) && is_string($_POST['operator'])
            ? $_POST['operator']
            : '';

        $expression = sprintf('%s %s %s', $firstInput, $operator, $secondInput);

        try {
            $result = calculate($firstInput, $operator, $secondInput);
            $formattedResult = formatResult($result, $operator);
            $expression = sprintf('%s %s %s', trim($firstInput), $operator, trim($secondInput));

            $notice = $expression . ' = ' . $formattedResult;
            $noticeType = 'success';

            $_SESSION['calculator_history'][] = [
                'expression' => $expression,
                'result' => $formattedResult,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        } catch (InvalidInputException $exception) {
            logCalculatorError($exception, $expression);
            $notice = $exception->getMessage();
            $noticeType = 'error';
        } catch (InvalidOperatorException $exception) {
            logCalculatorError($exception, $expression);
            $notice = $exception->getMessage();
            $noticeType = 'error';
        } catch (DivisionByZeroException $exception) {
            logCalculatorError($exception, $expression);
            $notice = $exception->getMessage();
            $noticeType = 'error';
        } catch (OverflowException $exception) {
            logCalculatorError($exception, $expression);
            $notice = $exception->getMessage();
            $noticeType = 'error';
        } catch (Throwable $exception) {
            // Log unexpected PHP errors/exceptions, but show only a generic message.
            logCalculatorError($exception, $expression);
            $notice = 'An unexpected error occurred. Please check your input and try again.';
            $noticeType = 'error';
        }
    } else {
        $notice = 'The requested action is not recognized.';
        $noticeType = 'error';
        $exception = new InvalidOperatorException('Unrecognized form action.');
        logCalculatorError($exception, 'unknown action');
    }
}

$history = array_reverse($_SESSION['calculator_history']);
$selectedOperators = ['+', '-', '*', '/', '%', '**'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robust Calculator</title>
    <style>
        :root {
            --background: #f4f6fb;
            --surface: #ffffff;
            --text: #202938;
            --muted: #667085;
            --border: #e3e8ef;
            --primary: #465bd8;
            --primary-hover: #3549bd;
            --success-bg: #eaf8ef;
            --success-text: #176b3a;
            --error-bg: #fff0ef;
            --error-text: #a32922;
            --warning-bg: #fff7df;
            --warning-text: #805b00;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 36px 16px;
            background: var(--background);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .page-header { margin-bottom: 22px; text-align: center; }
        .page-header h1 { margin: 0 0 6px; font-size: 2rem; }
        .page-header p { margin: 0; color: var(--muted); }

        .panel {
            padding: 26px;
            margin-bottom: 22px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(20, 35, 60, 0.05);
        }

        .field-grid {
            display: grid;
            grid-template-columns: 1fr 110px 1fr;
            gap: 14px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.92rem;
            font-weight: 700;
        }

        input, select {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #cfd6e1;
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }

        input:focus, select:focus {
            outline: 3px solid rgba(70, 91, 216, 0.15);
            border-color: var(--primary);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            min-height: 42px;
            padding: 10px 17px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .calculate-button { background: var(--primary); color: #fff; }
        .calculate-button:hover { background: var(--primary-hover); }
        .clear-button { background: #eef1f5; color: #344054; }
        .clear-button:hover { background: #e1e6ed; }

        .message {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid transparent;
            border-radius: 9px;
            overflow-wrap: anywhere;
        }

        .message.success { background: var(--success-bg); color: var(--success-text); border-color: #c7ebd2; }
        .message.error { background: var(--error-bg); color: var(--error-text); border-color: #ffd1ce; }
        .message.warning { background: var(--warning-bg); color: var(--warning-text); border-color: #f3df9e; }

        h2 { margin: 0 0 16px; font-size: 1.25rem; }
        .empty-state { margin: 0; color: var(--muted); }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 11px 10px; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.03em; }
        tbody tr:last-child td { border-bottom: 0; }
        td.result { font-weight: 700; }

        @media (max-width: 600px) {
            body { padding: 22px 12px; }
            .panel { padding: 19px; }
            .field-grid { grid-template-columns: 1fr; gap: 12px; }
            .page-header h1 { font-size: 1.65rem; }
            .actions button { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
<main class="container">
    <header class="page-header">
        <h1>Robust Calculator</h1>
        <p>Perform calculations with clear feedback and a saved session history.</p>
    </header>

    <section class="panel" aria-label="Calculator">
        <?php if ($notice !== ''): ?>
            <div class="message <?= escapeHtml($noticeType) ?>" role="alert" aria-live="polite">
                <?= escapeHtml($notice) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="calculator.php">
            <div class="field-grid">
                <div>
                    <label for="first_number">First number</label>
                    <input
                        type="number"
                        step="any"
                        id="first_number"
                        name="first_number"
                        value="<?= escapeHtml($firstInput) ?>"
                        placeholder="e.g. 10"
                        required
                    >
                </div>

                <div>
                    <label for="operator">Operator</label>
                    <select id="operator" name="operator" required>
                        <?php foreach ($selectedOperators as $allowedOperator): ?>
                            <option
                                value="<?= escapeHtml($allowedOperator) ?>"
                                <?= $operator === $allowedOperator ? 'selected' : '' ?>
                            ><?= escapeHtml($allowedOperator) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="second_number">Second number</label>
                    <input
                        type="number"
                        step="any"
                        id="second_number"
                        name="second_number"
                        value="<?= escapeHtml($secondInput) ?>"
                        placeholder="e.g. 3"
                        required
                    >
                </div>
            </div>

            <div class="actions">
                <button class="calculate-button" type="submit" name="action" value="calculate">
                    Calculate
                </button>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="history-heading">
        <h2 id="history-heading">Calculation History</h2>

        <?php if (count($history) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Expression</th>
                        <th>Result</th>
                        <th>Timestamp</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?= escapeHtml((string) ($record['expression'] ?? '')) ?></td>
                            <td class="result"><?= escapeHtml((string) ($record['result'] ?? '')) ?></td>
                            <td><?= escapeHtml((string) ($record['timestamp'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">No calculations yet. Your successful calculations will appear here.</p>
        <?php endif; ?>

        <form method="POST" action="calculator.php" class="actions">
            <button class="clear-button" type="submit" name="action" value="clear_history">
                Clear History
            </button>
        </form>
    </section>
</main>
</body>
</html>