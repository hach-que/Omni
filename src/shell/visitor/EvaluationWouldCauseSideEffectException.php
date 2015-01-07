<?php

/**
 * Indicates that evaluation of this visitor would cause or potentially
 * cause a side effect to occur, and thus it was not evaluated because
 * side effects are disallowed.
 */
final class EvaluationWouldCauseSideEffectException extends Exception {
}