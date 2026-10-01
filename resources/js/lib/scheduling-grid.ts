// Mirrors App\Support\SchedulingGrid (PHP): the open 08:00–22:00 grid on quarter hours (ADR 0004).
export const GRID_START = '08:00';
export const GRID_END = '22:00';
export const GRID_STEP_MINUTES = 15;
/** The step in seconds, for `<input type="time" step>`. */
export const GRID_STEP_SECONDS = GRID_STEP_MINUTES * 60;
