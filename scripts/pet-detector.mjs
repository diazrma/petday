/**
 * Detector de pets gratuito e local (CLIP via Transformers.js, sem API externa).
 *
 * Uso: node scripts/pet-detector.mjs <caminho-da-imagem>
 * Saída (stdout, JSON): { "has_pet": bool, "score": 0..1, "label": "...", "top": [...] }
 *
 * Pessoas podem aparecer na foto; o que importa é existir um animal.
 */
import { pipeline, env, RawImage } from '@huggingface/transformers';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
env.cacheDir = process.env.PET_AI_CACHE_DIR || path.join(here, '..', 'storage', 'app', 'ai-models');

const MODEL = process.env.PET_AI_MODEL || 'Xenova/clip-vit-base-patch32';
const THRESHOLD = Number(process.env.PET_AI_THRESHOLD || 0.7);

// Rótulos que contam como "tem pet" (inclui pet no colo de pessoas)
const PET_LABELS = [
    'a photo of a dog', 'a photo of a puppy', 'a photo of a cat', 'a photo of a kitten',
    'a photo of a bird', 'a photo of a parrot', 'a photo of a rabbit', 'a photo of a hamster',
    'a photo of a guinea pig', 'a photo of a fish in an aquarium', 'a photo of a turtle',
    'a photo of a lizard', 'a photo of a snake', 'a photo of a horse', 'a photo of a ferret',
    'a photo of a person holding a pet', 'a photo of a person with a dog', 'a photo of a person with a cat',
];
// Rótulos "negativos" que competem com os de pet
const OTHER_LABELS = [
    'a photo of a person', 'a selfie of a person', 'a photo of a group of people',
    'a photo of food', 'a photo of a landscape', 'a photo of a building', 'a photo of a car',
    'a screenshot of text', 'a meme with text', 'a photo of furniture', 'a photo of an object',
    'a stuffed animal toy', 'a cartoon drawing',
];

const file = process.argv[2];
if (!file) {
    console.error('Uso: node scripts/pet-detector.mjs <imagem>');
    process.exit(2);
}

try {
    const classifier = await pipeline('zero-shot-image-classification', MODEL, { dtype: 'q8' });
    // Lê os bytes direto: uploads do PHP chegam como arquivos temporários sem extensão
    const image = await RawImage.fromBlob(new Blob([await readFile(file)]));
    const results = await classifier(image, [...PET_LABELS, ...OTHER_LABELS]);

    const petSet = new Set(PET_LABELS);
    const score = results.filter((r) => petSet.has(r.label)).reduce((s, r) => s + r.score, 0);
    const best = results.find((r) => petSet.has(r.label));
    // O rótulo mais provável precisa ser de pet: evita que vários rótulos fracos somados aprovem selfies/imagens vazias
    const topIsPet = petSet.has(results[0].label);

    process.stdout.write(JSON.stringify({
        has_pet: topIsPet && score >= THRESHOLD,
        score: Number(score.toFixed(4)),
        label: best?.label.replace(/^a photo of (a |an )?/, '') ?? null,
        top: results.slice(0, 3).map((r) => ({ label: r.label, score: Number(r.score.toFixed(4)) })),
    }));
} catch (e) {
    console.error(e?.message || String(e));
    process.exit(1);
}
