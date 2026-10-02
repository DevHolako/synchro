/** How a module is named on screen: code, then name (mirrors `Module::label()` in PHP). */
export function moduleLabel(module: { code: string; name: string }): string {
    return `${module.code} · ${module.name}`;
}
