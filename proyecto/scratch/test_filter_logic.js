const fs = require('fs');

const catalogo = JSON.parse(fs.readFileSync('c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/proyecto/scratch/cursos_catalog.json', 'utf8'));

console.log('Catalogo total:', catalogo.length);
const manana = catalogo.filter(c => (c.jornada || '').toLowerCase() === 'mañana');
const tarde = catalogo.filter(c => (c.jornada || '').toLowerCase() === 'tarde');

console.log('Mañana count:', manana.length);
console.log('Tarde count:', tarde.length);

function testFilter(jornada) {
    let result = [];
    catalogo.forEach(c => {
        const jor = (c.jornada && c.jornada.toLowerCase() === 'tarde') ? 'Tarde' : 'Mañana';
        if (jornada === 'todas' || jornada.toLowerCase() === jor.toLowerCase()) {
            result.push(c);
        }
    });
    return result;
}

console.log('Filter todas count:', testFilter('todas').length);
console.log('Filter Mañana count:', testFilter('Mañana').length);
console.log('Filter Tarde count:', testFilter('Tarde').length);
