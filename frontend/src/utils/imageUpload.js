const COMPRESSIBLE_TYPES = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])

function loadImageFromFile(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => {
      URL.revokeObjectURL(url)
      resolve(img)
    }
    img.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('Не удалось прочитать изображение'))
    }
    img.src = url
  })
}

function canvasToBlob(canvas, mimeType, quality) {
  return new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        reject(new Error('Не удалось сжать изображение'))
        return
      }
      resolve(blob)
    }, mimeType, quality)
  })
}

export async function compressImageFile(file, opts = {}) {
  if (!file || !COMPRESSIBLE_TYPES.has((file.type || '').toLowerCase())) {
    return file
  }

  const {
    maxWidth = 1920,
    maxHeight = 1920,
    quality = 0.82,
    minBytesToCompress = 1024 * 1024,
  } = opts

  if (file.size < minBytesToCompress) {
    return file
  }

  const img = await loadImageFromFile(file)
  const ratio = Math.min(maxWidth / img.width, maxHeight / img.height, 1)
  const targetWidth = Math.max(1, Math.round(img.width * ratio))
  const targetHeight = Math.max(1, Math.round(img.height * ratio))

  const canvas = document.createElement('canvas')
  canvas.width = targetWidth
  canvas.height = targetHeight
  const ctx = canvas.getContext('2d')
  if (!ctx) return file

  ctx.drawImage(img, 0, 0, targetWidth, targetHeight)

  const outType = file.type.toLowerCase() === 'image/png' ? 'image/png' : 'image/jpeg'
  const blob = await canvasToBlob(canvas, outType, quality)
  if (blob.size >= file.size) {
    return file
  }

  const ext = outType === 'image/png' ? 'png' : 'jpg'
  const baseName = (file.name || 'photo').replace(/\.[^.]+$/, '')
  const name = `${baseName}.${ext}`
  return new File([blob], name, { type: outType, lastModified: Date.now() })
}

export async function compressImagesInList(files, opts = {}) {
  const out = []
  for (const file of files || []) {
    try {
      out.push(await compressImageFile(file, opts))
    } catch {
      out.push(file)
    }
  }
  return out
}
