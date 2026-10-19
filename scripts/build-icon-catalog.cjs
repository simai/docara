#!/usr/bin/env node
"use strict";

/*
 * Regenerate Docara's Material Symbols icon catalogue:
 *
 *   node scripts/build-icon-catalog.cjs --ui-builder /path/to/simai/ui-builder
 *
 * The catalogue is a set of static font instances, one per family (outlined,
 * rounded, sharp), weight (300, 400, 500: the light, regular and medium an
 * author can ask for) and fill (0, 1), each holding every icon name of the
 * pinned source font, with GRAD 0 and opsz 24. A page downloads only the
 * instances its icons use, about 0.3 MB each, instead of a 3.5 to 5.3 MB
 * variable font.
 *
 * Every instance is made by ui-builder's own icon subset generator (the one
 * that made the shell subset), from the source fonts Docara already bundles.
 * The script then shapes every icon name in every instance and requires the
 * outline to equal the source font's outline at the same axis values. Output
 * that exists with different bytes is refused, so a run over an unchanged
 * catalogue proves it reproducible.
 *
 * The index also records which names the shell subset draws. hb-subset keeps
 * every ligature whose letters the subset maps, so the subset draws far more
 * than the names it was asked for: exactly the names written only with the
 * characters of those names. The script proves that rule by shaping every
 * name against the subset, and the icon runtime then sends only the other
 * names to the catalogue.
 *
 * The script needs Node.js 20+ and a ui-builder checkout with its
 * dependencies installed; it is a maintainer tool, not part of a site build.
 */

const crypto = require("node:crypto");
const fs = require("node:fs");
const path = require("node:path");
const { createRequire } = require("node:module");

const FAMILIES = { outlined: "Outlined", rounded: "Rounded", sharp: "Sharp" };
const WEIGHTS = [300, 400, 500];
const FILLS = [0, 1];
const SCHEMA = "docara.icon_catalog.v1";
const PROVIDER = "google/material-design-icons";

function argument(name, fallback = null)
{
    const prefix = `--${name}=`;
    const inline = process.argv.find(value => value.startsWith(prefix));
    if (inline)
    {
        return inline.slice(prefix.length);
    }
    const index = process.argv.indexOf(`--${name}`);

    return index === -1 ? fallback : process.argv[index + 1];
}

function sha256(bytes)
{
    return crypto.createHash("sha256").update(bytes).digest("hex");
}

function writeExact(file, bytes)
{
    const next = Buffer.isBuffer(bytes) ? bytes : Buffer.from(bytes);
    if (fs.existsSync(file))
    {
        if (!fs.readFileSync(file).equals(next))
        {
            throw new Error(`Refusing to overwrite changed output: ${file}`);
        }
        return;
    }
    fs.writeFileSync(file, next, { flag: "wx" });
}

async function main()
{
    const repository = path.resolve(__dirname, "..");
    const uiBuilder = argument("ui-builder");
    if (!uiBuilder)
    {
        throw new Error("Usage: build-icon-catalog.cjs --ui-builder /path/to/ui-builder [--source-revision REV]");
    }
    const builderRoot = path.resolve(uiBuilder);
    const requireFromBuilder = createRequire(path.join(builderRoot, "package.json"));
    const { buildIconSubset, canonicalJson } = require(path.join(builderRoot, "libs/iconSubset.js"));
    const fontverter = requireFromBuilder("fontverter");
    const harfbuzz = await requireFromBuilder("harfbuzzjs");
    const generatorRevision = `sha256:${sha256(fs.readFileSync(path.join(builderRoot, "libs/iconSubset.js")))}`;

    const sourceRoot = path.join(repository, "resources/portable/vendor/google/material-symbols");
    const revisions = fs.readdirSync(sourceRoot).filter(name => /^[a-f0-9]{40}$/.test(name));
    const revision = argument("source-revision", revisions.length === 1 ? revisions[0] : null);
    if (!revision || !revisions.includes(revision))
    {
        throw new Error(`Pass --source-revision; bundled revisions: ${revisions.join(", ") || "none"}`);
    }
    const publicBase = `/_docara/vendor/docara/icon-catalog/${revision}`;
    const outputRoot = path.join(repository, "resources/portable/vendor/docara/icon-catalog", revision);
    fs.mkdirSync(outputRoot, { recursive: true });

    function open(truetype)
    {
        const blob = harfbuzz.createBlob(truetype);
        const face = harfbuzz.createFace(blob, 0);
        const font = harfbuzz.createFont(face);

        return { font, destroy() { font.destroy(); face.destroy(); blob.destroy(); } };
    }

    function outline(font, name)
    {
        const buffer = harfbuzz.createBuffer();
        try
        {
            buffer.addText(name);
            buffer.guessSegmentProperties();
            harfbuzz.shape(font, buffer);
            const glyphs = buffer.json(font);

            return glyphs.length === 1 && glyphs[0].g !== 0 ? font.glyphToPath(glyphs[0].g) : null;
        }
        finally
        {
            buffer.destroy();
        }
    }

    // A static instance stores whole font units, while the variable source
    // interpolates fractional ones: the same drawing commands, and every
    // coordinate within one unit (of 960 per em) of the source.
    function sameOutline(instance, source)
    {
        if (instance === null || source === null)
        {
            return false;
        }
        const commands = text => text.replace(/-?\d+(?:\.\d+)?/g, "#");
        if (commands(instance) !== commands(source))
        {
            return false;
        }
        const left = instance.match(/-?\d+(?:\.\d+)?/g) || [];
        const right = source.match(/-?\d+(?:\.\d+)?/g) || [];

        return left.length === right.length
            && left.every((value, index) => Math.abs(Number(value) - Number(right[index])) <= 1);
    }

    let names = null;
    const instances = [];
    for (const [family, fileFamily] of Object.entries(FAMILIES))
    {
        const sourcePath = path.join(sourceRoot, revision, `MaterialSymbols${fileFamily}.woff2`);
        const source = fs.readFileSync(sourcePath);
        const full = open(await fontverter.convert(source, "truetype"));
        // Every glyph named like an icon that the font shapes from that name.
        const familyNames = new Set();
        for (let glyph = 1; ; glyph += 1)
        {
            const name = full.font.glyphName(glyph);
            if (!name || /^gid\d+$/.test(name))
            {
                break;
            }
            if (/^[a-z0-9][a-z0-9_]*$/.test(name) && outline(full.font, name) !== null)
            {
                familyNames.add(name);
            }
        }
        const sorted = [...familyNames].sort((left, right) => left < right ? -1 : left > right ? 1 : 0);
        if (names === null)
        {
            names = sorted;
        }
        else if (sorted.join("\n") !== names.join("\n"))
        {
            throw new Error(`The ${family} font names other icons than the outlined font`);
        }

        for (const weight of WEIGHTS)
        {
            for (const fill of FILLS)
            {
                const result = await buildIconSubset({
                    axes: { FILL: [fill], GRAD: [0], opsz: [24], wght: [weight] },
                    family,
                    generatorRevision,
                    icons: names,
                    license: "Apache-2.0",
                    provenance: PROVIDER,
                    publicBase,
                    source,
                    sourceRevision: revision,
                    sourceSha256: sha256(source),
                });
                const instance = open(await fontverter.convert(result.font, "truetype"));
                full.font.setVariations({ FILL: fill, GRAD: 0, opsz: 24, wght: weight });
                const different = names.filter(name => !sameOutline(outline(instance.font, name), outline(full.font, name)));
                instance.destroy();
                if (different.length)
                {
                    throw new Error(`${family} ${weight}/${fill} differs from the source for ${different.length} icons, e.g. ${different.slice(0, 5).join(", ")}`);
                }
                writeExact(path.join(outputRoot, result.files.font), result.font);
                writeExact(path.join(outputRoot, result.files.css), result.css);
                writeExact(path.join(outputRoot, result.files.manifest), result.manifestJson);
                instances.push({
                    family,
                    fill,
                    font: { path: result.files.font, sha256: result.manifest.files.font.sha256, size: result.font.length },
                    manifest: { path: result.files.manifest, sha256: sha256(result.manifestJson) },
                    weight,
                });
                process.stderr.write(`${family} ${weight}/${fill}: ${result.font.length} bytes, ${names.length} icons, exact\n`);
            }
        }
        full.destroy();
    }

    const subsetRoot = path.join(repository, "resources/portable/vendor/docara/icon-subset", revision);
    const subsetManifests = fs.readdirSync(subsetRoot).filter(name => name.endsWith(".manifest.json"));
    if (subsetManifests.length !== 1)
    {
        throw new Error(`Expected one shell subset manifest in ${subsetRoot}`);
    }
    const subsetManifest = JSON.parse(fs.readFileSync(path.join(subsetRoot, subsetManifests[0]), "utf8"));
    const subsetFont = fs.readFileSync(path.join(subsetRoot, subsetManifest.files.font.path));
    if (sha256(subsetFont) !== subsetManifest.files.font.sha256)
    {
        throw new Error("The shell subset font does not match its manifest");
    }
    const characters = [...new Set(subsetManifest.icons.join(""))].sort().join("");
    const subset = open(await fontverter.convert(subsetFont, "truetype"));
    let covered = 0;
    for (const name of names)
    {
        const drawn = outline(subset.font, name) !== null;
        const written = [...name].every(character => characters.includes(character));
        if (drawn !== written)
        {
            throw new Error(`The shell subset ${drawn ? "draws" : "lacks"} ${name}, against the character rule`);
        }
        covered += drawn ? 1 : 0;
    }
    subset.destroy();

    const index = {
        axes: { GRAD: 0, opsz: 24 },
        generator: { id: require(path.join(builderRoot, "libs/iconSubset.js")).GENERATOR, revision: generatorRevision },
        icons: { count: names.length, sha256: sha256(names.join("\n")) },
        instances,
        license: "Apache-2.0",
        schema: SCHEMA,
        shell_subset: {
            characters,
            covered,
            font_sha256: subsetManifest.files.font.sha256,
        },
        source: { provider: PROVIDER, revision },
    };
    writeExact(path.join(outputRoot, "icon-catalog.json"), canonicalJson(index));
    process.stdout.write(`${JSON.stringify({ instances: instances.length, icons: names.length, output_root: outputRoot }, null, 2)}\n`);
}

main().catch(error => {
    process.stderr.write(`${error && error.stack ? error.stack : error}\n`);
    process.exitCode = 1;
});
