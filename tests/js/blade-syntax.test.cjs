// Syntax-check inline JavaScript with server-side Blade values replaced by inert placeholders.
// HTTP feature tests separately render these templates with real server-side values.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
function files(directory) {
    return fs.readdirSync(directory, {withFileTypes: true}).flatMap(entry => entry.isDirectory()
        ? files(path.join(directory, entry.name)) : [path.join(directory, entry.name)]);
}
function maskDirectives(source) {
    const expression = /@(json|js|if|elseif|foreach|unless)\(/g;
    let match, output = '', position = 0;
    while ((match = expression.exec(source))) {
        let index = expression.lastIndex, depth = 1, quote = null;
        for (; index < source.length && depth; index++) {
            const character = source[index];
            if (quote) {
                if (character === '\\') index++;
                else if (character === quote) quote = null;
            } else if (character === '"' || character === "'") quote = character;
            else if (character === '(') depth++;
            else if (character === ')') depth--;
        }
        output += source.slice(position, match.index) + (['json', 'js'].includes(match[1]) ? 'null' : '');
        position = index;
        expression.lastIndex = index;
    }
    return (output + source.slice(position)).replace(/@(else|endif|endforeach|endunless)\b/g, '');
}
let count = 0;
for (const file of files('resources/views')) {
    const template = fs.readFileSync(file, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '')
        .replace(/\{\{[\s\S]*?\}\}|\{!![\s\S]*?!!\}/g, 'BLADE_VALUE');
    for (const match of template.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
        if (/type=["']application\/(?:ld\+)?json/.test(match[1])) continue;
        if (!match[2].trim()) continue;
        const source = maskDirectives(match[2]);
        new vm.Script(source, {filename: file});
        count++;
    }
}
console.log(`${count} inline JavaScript blocks passed syntax checks`);
