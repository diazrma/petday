// Detector gratuito de pets em fotos usando CLIP (zero-shot) via Transformers.js.
// Roda localmente no servidor: sem chave de API e sem custo por imagem.
// Uso: node scripts/detect-pet.mjs caminho/da/imagem.jpg
// Saída (JSON): { "has_pet": bool, "score": 0..1, "label": "...", "labels": [...] }
import { pipeline, env } from '@huggingface/transformers';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
env.cacheDir = path.join(here, '..', 'storage', 'app', 'ai-models');

const PET_LABELS = {
    'a photo of a dog': 'cachorro',
    'a photo of a cat': 'gato',
    'a photo of a bird': 'ave',
    'a photo of a rabbit': 'coelho',
    'a photo of a hamster or guinea pig': 'roedor',
    'a photo of a fish in an aquarium': 'peixe',
    'a photo of a turtle or lizard': 'réptil',
    'a photo of a horse': 'cavalo',
    'a photo of a person holding a pet': 'pessoa com pet',
};
const OTHER_LABELS = [
    'a photo of a person',
    'a selfie of a person',
    'a photo of food',
    'a landscape photo',
    'a screenshot of text',
    'a photo of an object',
    'a photo of a car',
    'a meme or drawing',
    'a blank or plain colored image',
];

const file = process.argv[2];
if (!file) {
    console.log(JSON.stringify({ error: 'missing file' }));
    process.exit(2);
}

try {
    const classify = await pipeline('zero-shot-image-classification', 'Xenova/clip-vit-base-patch32', { dtype: 'q8' });
    const candidates = [...Object.keys(PET_LABELS), ...OTHER_LABELS];
    const results = await classify(file, candidates);

    const petScore = results.filter((r) => r.label in PET_LABELS).reduce((sum, r) => sum + r.score, 0);
    const top = results[0];
    const topPet = results.find((r) => r.label in PET_LABELS);

    console.log(JSON.stringify({
        has_pet: petScore >= Number(process.env.PET_AI_THRESHOLD ?? 0.6),
        score: Number(petScore.toFixed(4)),
        label: topPet ? PET_LABELS[topPet.label] : null,
        top: top.label,
        labels: results.slice(0, 4).map((r) => ({ label: r.label, score: Number(r.score.toFixed(4)) })),
    }));
} catch (e) {
    console.log(JSON.stringify({ error: String(e?.message ?? e) }));
    process.exit(1);
}
