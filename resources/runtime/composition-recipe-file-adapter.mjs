import { readFile, realpath } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { isAbsolute, relative, resolve } from 'node:path';
import { createInterface } from 'node:readline';
import { pathToFileURL } from 'node:url';

const [projectArgument, frameworkArgument, descriptorArgument] = process.argv.slice(2);

function fail(code, message) {
  throw Object.assign(new Error(message), { code });
}

const projectRoot = await realpath(projectArgument).catch(() => fail('project_root_invalid', 'Project root is unavailable.'));
const frameworkEntry = await realpath(frameworkArgument).catch(() => fail('framework_entry_invalid', 'Framework entry is unavailable.'));

async function projectFile(path) {
  if (typeof path !== 'string' || path === '' || isAbsolute(path)) fail('project_path_invalid', 'A project-relative path is required.');
  const target = await realpath(resolve(projectRoot, path)).catch(() => fail('project_file_missing', path));
  const relation = relative(projectRoot, target);
  if (relation.startsWith('..') || isAbsolute(relation)) fail('project_path_escape', path);
  return target;
}

const framework = await import(pathToFileURL(frameworkEntry));
const parse = framework.parseRecipeJson;

async function compile(descriptorArgument) {
  const descriptorPath = await projectFile(descriptorArgument);
  const descriptor = parse(await readFile(descriptorPath, 'utf8'));
  const recipePath = await projectFile(descriptor.recipe);
  const inputsPath = await projectFile(descriptor.inputs);
  const recipe = parse(await readFile(recipePath, 'utf8'));
  const inputs = parse(await readFile(inputsPath, 'utf8'));
  const sourceIndex = new Map();
  for (const source of descriptor.sources || []) {
    const key = JSON.stringify([source.kind, source.owner, source.ref]);
    if (sourceIndex.has(key)) fail('source_duplicate', source.ref);
    sourceIndex.set(key, source);
  }

  const result = await framework.resolveRecipe(recipe, {
    trustedContext: { scope: descriptor.scope },
    inputs,
    executionContract: descriptor.executionContract,
    ports: {
      readReference: async (reference) => {
        const source = sourceIndex.get(JSON.stringify([reference.kind, reference.owner, reference.ref]));
        if (!source) return { status: 'missing' };
        const path = await projectFile(source.path);
        return { source: parse(await readFile(path, 'utf8')), revision: source.revision, generation: source.generation };
      },
    },
  });
  if (!result.document) return result;
  const rendered = await framework.render(result.document);
  if (rendered.diagnostics.length) fail('render_failed', rendered.diagnostics[0].message);
  return { ...result, html: rendered.html, assets: rendered.assets, documentDigest: rendered.digest };
}

async function resolveGenerated(request) {
  const manifests = (Array.isArray(request.manifests) ? request.manifests : [])
    .slice().sort((left, right) => left.type < right.type ? -1 : left.type > right.type ? 1 : 0);
  const registry = framework.createRegistry(manifests);
  return framework.resolveRecipe(request.recipe, {
    registry,
    trustedContext: { scope: request.inputs?.scope },
    inputs: request.inputs,
    executionContract: {
      ...request.executionContract,
      registryDigest: `sha256:${createHash('sha256').update(framework.stableStringify(manifests)).digest('hex')}`,
    },
    ports: {
      readReference: async () => ({ status: 'missing' }),
    },
  });
}

function sessionError(error) {
  return {
    sessionError: {
      code: typeof error?.code === 'string' ? error.code : 'compile_failed',
      message: error instanceof Error ? error.message : String(error),
    },
  };
}

if (descriptorArgument === '--session') {
  const lines = createInterface({ input: process.stdin, crlfDelay: Infinity });
  for await (const line of lines) {
    if (line.trim() === '') continue;
    try {
      const request = parse(line);
      const result = request.operation === 'resolve'
        ? await resolveGenerated(request)
        : await compile(request.descriptor);
      process.stdout.write(`${JSON.stringify(result)}\n`);
    } catch (error) {
      process.stdout.write(`${JSON.stringify(sessionError(error))}\n`);
    }
  }
} else {
  try {
    process.stdout.write(JSON.stringify(await compile(descriptorArgument)));
  } catch (error) {
    process.stderr.write(JSON.stringify(sessionError(error)));
    process.exitCode = 2;
  }
}
