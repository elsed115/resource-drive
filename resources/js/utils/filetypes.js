// utils/filetypes.js

const iconMap = {
  '3ds': '001-3ds',
  ai: '002-ai format',
  app: '003-app',
  asp: '004-asp',
  bat: '005-bat',
  'c++': '006-c++',
  cs: '007-c sharp',
  css: '008-css',
  csv: '009-csv',
  dat: '010-dat format',
  dll: '011-dll',
  doc: '012-doc',
  docx: '013-docx',
  dwg: '014-dwg',
  eml: '015-eml',
  eps: '016-eps',
  exe: '017-exe',
  flv: '018-flv',
  gif: '019-gif',
  html: '020-html',
  ics: '021-ics format',
  iso: '022-iso',
  jar: '023-jar',
  jpeg: '024-jpeg',
  jpg: '025-jpg',
  js: '026-js format',
  log: '027-log format',
  mdb: '028-mdb',
  mov: '029-mov',
  mp3: '030-mp3',
  mp4: '031-mp4',
  pdf: '032-pdf',
  obj: '033-obj',
  otf: '034-otf',
  php: '035-php',
  png: '036-png',
  ppt: '037-ppt',
  psd: '038-psd',
  pub: '039-pub',
  rar: '040-rar',
  sql: '041-sql',
  srt: '042-srt',
  svg: '043-svg',
  ttf: '044-ttf',
  txt: '045-txt',
  wav: '046-wav format',
  xls: '047-xls',
  xlsx: '048-xlsx',
  xml: '049-xml',
  zip: '050-zip',
};

export function isImage(item) {
  return item.type === 'file' && /\.(jpg|jpeg|png|gif|webp|svg|psd|ai|eps)$/i.test(item.name);
}


export function getFileIcon(item) {
  if (item.type === 'folder') {
    return null;
  }
  const ext = item.name.split('.').pop().toLowerCase();
  const iconName = iconMap[ext] || 'default-file';
  // ora l’URL punta a /storage/svg/ICON.svg
  return `/storage/svg/${iconName}.svg`;
}

export function onImageError(event) {
  console.warn('[FileManager] Image failed to load:', event.target.src);
  event.target.style.display = 'none';
  // fallback eventuale...
}

// Nuovi helper per tipi specifici
export function isPdf(item) {
  return item.type === 'file' && /\.pdf$/i.test(item.name);
}

export function isWord(item) {
  return item.type === 'file' && /\.(docx?|doc)$/i.test(item.name);
}

export function isExcel(item) {
  return item.type === 'file' && /\.(xlsx?|xls)$/i.test(item.name);
}

export function isArchive(item) {
  return item.type === 'file' && /\.(zip|rar|7z|tar|gz)$/i.test(item.name);
}
