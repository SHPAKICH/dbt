<template>
  <div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Конструктор карточек профиля</h1>
      <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Новый шаблон
      </button>
    </div>

    <p v-if="loading" class="text-gray-500 py-8 text-center">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>

    <!-- Список шаблонов -->
    <div v-else-if="!editingItem" class="space-y-4">
      <p v-if="!items.length" class="text-gray-500 py-8 text-center">Шаблонов пока нет. Создайте первый!</p>
      <div
        v-for="item in items"
        :key="item.id"
        class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col md:flex-row md:items-center gap-4"
      >
        <!-- Preview mini -->
        <div class="shrink-0">
          <div
            class="w-[220px] h-[72px] rounded-lg flex items-center gap-3 overflow-hidden transition-all"
            :class="cardEffectClass(item.styleConfig)"
            :style="cardConfigToStyle(item.styleConfig)"
          >
            <div
              class="shrink-0 rounded-full bg-white/30 flex items-center justify-center text-sm font-bold ml-3"
              :style="{ ...cardAvatarStyle(item.styleConfig), backgroundColor: 'rgba(255,255,255,0.2)' }"
            >
              <span :style="cardNameStyle(item.styleConfig)">И</span>
            </div>
            <div class="min-w-0">
              <div class="truncate" :style="cardNameStyle(item.styleConfig)">Иван Иванов</div>
              <div class="truncate" :style="cardStatusStyle(item.styleConfig)">Тимейкер</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-gray-800">{{ item.name }}</div>
          <p class="text-sm text-gray-500">{{ item.code }} · {{ item.isActive ? 'Активен' : 'Неактивен' }}</p>
          <p v-if="item.description" class="text-sm text-gray-400 mt-0.5">{{ item.description }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button @click="startEdit(item)" class="px-3 py-1.5 text-sm rounded-lg bg-primary text-white hover:bg-primary-dark">Редактировать</button>
          <button @click="duplicateItem(item)" class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Дублировать</button>
          <button @click="confirmDelete(item)" class="px-3 py-1.5 text-sm rounded-lg border border-red-300 text-red-600 hover:bg-red-50">Удалить</button>
        </div>
      </div>
    </div>

    <!-- Конструктор (редактор) -->
    <div v-if="editingItem" class="space-y-6">
      <div class="flex items-center gap-3 mb-2">
        <button @click="cancelEdit" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <h2 class="text-xl font-bold text-gray-800">{{ isNew ? 'Новый шаблон' : `Редактирование: ${editingItem.name}` }}</h2>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <!-- Панель настроек -->
        <div class="space-y-4">
          <!-- Основные поля -->
          <section class="card-section">
            <h3 class="section-title">Основные данные</h3>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="field-label">Название</label>
                <input v-model="editingItem.name" class="field-input" placeholder="Gold Premium" />
              </div>
              <div>
                <label class="field-label">Код (уникальный)</label>
                <input v-model="editingItem.code" class="field-input" placeholder="gold_premium" />
              </div>
            </div>
            <div class="mt-3">
              <label class="field-label">Описание</label>
              <input v-model="editingItem.description" class="field-input" placeholder="Описание для пользователей" />
            </div>
            <div class="flex items-center gap-3 mt-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="editingItem.isActive" class="accent-primary w-4 h-4" />
                <span class="text-sm text-gray-700">Активен</span>
              </label>
            </div>
          </section>

          <!-- Фон -->
          <section class="card-section">
            <h3 class="section-title">Фон</h3>
            <div class="flex gap-2 mb-3">
              <button
                v-for="opt in [{ v: 'solid', l: 'Цвет' }, { v: 'gradient', l: 'Градиент' }, { v: 'image', l: 'Изображение' }]"
                :key="opt.v"
                @click="cfg.bg.type = opt.v"
                class="px-3 py-1 text-sm rounded-full border transition-colors"
                :class="cfg.bg.type === opt.v ? 'bg-primary text-white border-primary' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              >{{ opt.l }}</button>
            </div>

            <div v-if="cfg.bg.type === 'solid'">
              <label class="field-label">Цвет фона</label>
              <div class="flex items-center gap-3">
                <input type="color" v-model="cfg.bg.color" class="color-picker" />
                <input v-model="cfg.bg.color" class="field-input w-32" />
              </div>
            </div>

            <div v-if="cfg.bg.type === 'gradient'" class="space-y-3">
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="p in gradientPresets"
                  :key="p.name"
                  @click="applyGradientPreset(p)"
                  class="h-7 w-14 rounded-md border border-gray-200 hover:ring-2 hover:ring-primary/40 transition-all"
                  :style="{ background: presetGradientCss(p) }"
                  :title="p.name"
                />
              </div>
              <div class="flex items-center gap-3">
                <label class="field-label mb-0">Тип</label>
                <select v-model="cfg.bg.gradient.type" class="field-input w-32">
                  <option value="linear">Линейный</option>
                  <option value="radial">Радиальный</option>
                </select>
                <template v-if="cfg.bg.gradient.type === 'linear'">
                  <label class="field-label mb-0 ml-2">Угол</label>
                  <input type="range" v-model.number="cfg.bg.gradient.angle" min="0" max="360" class="w-28" />
                  <span class="text-sm text-gray-500 w-10">{{ cfg.bg.gradient.angle }}°</span>
                </template>
              </div>
              <div class="space-y-2">
                <div v-for="(stop, i) in cfg.bg.gradient.stops" :key="i" class="flex items-center gap-2">
                  <input type="color" v-model="stop.color" class="color-picker" />
                  <input v-model="stop.color" class="field-input w-28" />
                  <input type="range" v-model.number="stop.pos" min="0" max="100" class="flex-1" />
                  <span class="text-xs text-gray-500 w-10">{{ stop.pos }}%</span>
                  <button v-if="cfg.bg.gradient.stops.length > 2" @click="cfg.bg.gradient.stops.splice(i, 1)" class="text-red-400 hover:text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
                <button @click="addGradientStop" class="text-sm text-primary hover:underline">+ Добавить точку</button>
              </div>
            </div>

            <div v-if="cfg.bg.type === 'image'" class="space-y-3">
              <div class="flex items-center gap-3">
                <input type="color" v-model="cfg.bg.color" class="color-picker" title="Фоновый цвет (под изображением)" />
                <input v-model="cfg.bg.color" class="field-input w-32" placeholder="Fallback цвет" />
              </div>
              <div>
                <label class="field-label">PNG-изображение</label>
                <div class="flex items-center gap-3">
                  <input v-model="cfg.bg.image.url" class="field-input flex-1" placeholder="URL изображения" />
                  <label class="px-3 py-2 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 cursor-pointer shrink-0">
                    Загрузить
                    <input type="file" accept="image/png" class="hidden" @change="uploadBgImage" />
                  </label>
                </div>
                <p v-if="uploadingBg" class="text-xs text-gray-400 mt-1">Загрузка...</p>
              </div>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="field-label">Размер</label>
                  <select v-model="cfg.bg.image.size" class="field-input">
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="auto">Auto</option>
                    <option value="100% 100%">Stretch</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Позиция</label>
                  <select v-model="cfg.bg.image.position" class="field-input">
                    <option value="center">Центр</option>
                    <option value="top">Верх</option>
                    <option value="bottom">Низ</option>
                    <option value="left">Лево</option>
                    <option value="right">Право</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Повтор</label>
                  <select v-model="cfg.bg.image.repeat" class="field-input">
                    <option value="no-repeat">Нет</option>
                    <option value="repeat">Повтор</option>
                    <option value="repeat-x">По X</option>
                    <option value="repeat-y">По Y</option>
                  </select>
                </div>
              </div>
            </div>
          </section>

          <!-- Рамка -->
          <section class="card-section">
            <h3 class="section-title">Рамка</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="field-label">Толщина</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.border.width" min="0" max="8" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.border.width }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">Стиль</label>
                <select v-model="cfg.border.style" class="field-input">
                  <option value="solid">Сплошная</option>
                  <option value="dashed">Пунктир</option>
                  <option value="dotted">Точки</option>
                  <option value="double">Двойная</option>
                  <option value="none">Нет</option>
                </select>
              </div>
              <div>
                <label class="field-label">Цвет</label>
                <div class="flex items-center gap-2">
                  <input type="color" v-model="cfg.border.color" class="color-picker" />
                  <input v-model="cfg.border.color" class="field-input flex-1 !px-1.5" />
                </div>
              </div>
              <div>
                <label class="field-label">Скругление</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.border.radius" min="0" max="32" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.border.radius }}</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Тень -->
          <section class="card-section">
            <h3 class="section-title">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="cfg.shadow.enabled" class="accent-primary w-4 h-4" />
                Тень
              </label>
            </h3>
            <div v-if="cfg.shadow.enabled" class="grid grid-cols-2 sm:grid-cols-5 gap-3">
              <div>
                <label class="field-label">X</label>
                <input type="range" v-model.number="cfg.shadow.x" min="-20" max="20" class="w-full" />
                <span class="text-xs text-gray-400 block text-center">{{ cfg.shadow.x }}</span>
              </div>
              <div>
                <label class="field-label">Y</label>
                <input type="range" v-model.number="cfg.shadow.y" min="-20" max="20" class="w-full" />
                <span class="text-xs text-gray-400 block text-center">{{ cfg.shadow.y }}</span>
              </div>
              <div>
                <label class="field-label">Размытие</label>
                <input type="range" v-model.number="cfg.shadow.blur" min="0" max="40" class="w-full" />
                <span class="text-xs text-gray-400 block text-center">{{ cfg.shadow.blur }}</span>
              </div>
              <div>
                <label class="field-label">Растяжение</label>
                <input type="range" v-model.number="cfg.shadow.spread" min="-10" max="20" class="w-full" />
                <span class="text-xs text-gray-400 block text-center">{{ cfg.shadow.spread }}</span>
              </div>
              <div>
                <label class="field-label">Цвет</label>
                <input v-model="cfg.shadow.color" class="field-input" placeholder="rgba(0,0,0,0.2)" />
              </div>
            </div>
          </section>

          <!-- Текст имени -->
          <section class="card-section">
            <h3 class="section-title">Имя пользователя</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="field-label">Цвет</label>
                <div class="flex items-center gap-2">
                  <input type="color" v-model="cfg.name.color" class="color-picker" />
                  <input v-model="cfg.name.color" class="field-input flex-1 !px-1.5" />
                </div>
              </div>
              <div>
                <label class="field-label">Размер</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.name.fontSize" min="10" max="24" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.name.fontSize }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">Жирность</label>
                <select v-model="cfg.name.fontWeight" class="field-input">
                  <option value="normal">Обычный</option>
                  <option value="500">Средний</option>
                  <option value="600">Полужирный</option>
                  <option value="bold">Жирный</option>
                  <option value="800">Extra Bold</option>
                </select>
              </div>
              <div>
                <label class="field-label">Шрифт</label>
                <select v-model="cfg.name.fontFamily" class="field-input">
                  <option v-for="f in fontFamilyOptions" :key="f.value" :value="f.value">{{ f.label }}</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
              <div>
                <label class="field-label">Тень текста</label>
                <input v-model="cfg.name.textShadow" class="field-input" placeholder="0 1px 2px rgba(0,0,0,0.3)" />
              </div>
              <div>
                <label class="field-label">Межбуквенный</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.name.letterSpacing" min="-2" max="6" step="0.5" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.name.letterSpacing }}</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Текст статуса -->
          <section class="card-section">
            <h3 class="section-title">Должность / статус</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div>
                <label class="field-label">Цвет</label>
                <div class="flex items-center gap-2">
                  <input type="color" :value="toHex(cfg.status.color)" @input="cfg.status.color = $event.target.value" class="color-picker" />
                  <input v-model="cfg.status.color" class="field-input flex-1 !px-1.5" />
                </div>
              </div>
              <div>
                <label class="field-label">Размер</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.status.fontSize" min="9" max="18" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.status.fontSize }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">Жирность</label>
                <select v-model="cfg.status.fontWeight" class="field-input">
                  <option value="normal">Обычный</option>
                  <option value="500">Средний</option>
                  <option value="600">Полужирный</option>
                  <option value="bold">Жирный</option>
                </select>
              </div>
            </div>
          </section>

          <!-- Аватар -->
          <section class="card-section">
            <h3 class="section-title">Аватар</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="field-label">Размер</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.avatar.size" min="28" max="64" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.avatar.size }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">Рамка</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.avatar.borderWidth" min="0" max="6" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.avatar.borderWidth }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">Цвет рамки</label>
                <div class="flex items-center gap-2">
                  <input type="color" :value="toHex(cfg.avatar.borderColor)" @input="cfg.avatar.borderColor = $event.target.value" class="color-picker" />
                  <input v-model="cfg.avatar.borderColor" class="field-input flex-1 !px-1.5" />
                </div>
              </div>
              <div>
                <label class="field-label">Скругление</label>
                <div class="flex items-center gap-2">
                  <input type="range" v-model.number="cfg.avatar.borderRadius" min="0" max="50" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.avatar.borderRadius }}%</span>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <label class="field-label">Тень аватара</label>
              <input v-model="cfg.avatar.shadow" class="field-input" placeholder="0 2px 8px rgba(0,0,0,0.3)" />
            </div>
          </section>

          <!-- Отступы -->
          <section class="card-section">
            <h3 class="section-title">Внутренние отступы</h3>
            <div class="grid grid-cols-4 gap-3">
              <div v-for="side in ['top', 'right', 'bottom', 'left']" :key="side">
                <label class="field-label">{{ { top: 'Верх', right: 'Право', bottom: 'Низ', left: 'Лево' }[side] }}</label>
                <div class="flex items-center gap-1">
                  <input type="range" v-model.number="cfg.padding[side]" min="0" max="40" class="flex-1" />
                  <span class="text-xs text-gray-500 w-6">{{ cfg.padding[side] }}</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Эффекты -->
          <section class="card-section">
            <h3 class="section-title">Спецэффект</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="eff in effectOptions"
                :key="eff.value"
                @click="cfg.effect = eff.value"
                class="px-3 py-1.5 text-sm rounded-full border transition-colors"
                :class="cfg.effect === eff.value ? 'bg-primary text-white border-primary' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              >{{ eff.label }}</button>
            </div>
          </section>

          <!-- Действия -->
          <div class="flex items-center gap-3 pt-2">
            <button @click="saveItem" :disabled="saving" class="btn-primary">
              {{ saving ? 'Сохранение...' : (isNew ? 'Создать шаблон' : 'Сохранить изменения') }}
            </button>
            <button @click="cancelEdit" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Отмена</button>
            <p v-if="saveMsg" class="text-sm text-green-600">{{ saveMsg }}</p>
            <p v-if="saveError" class="text-sm text-red-600">{{ saveError }}</p>
          </div>
        </div>

        <!-- Живой превью -->
        <div class="lg:sticky lg:top-20 space-y-4">
          <div class="bg-gray-100 rounded-xl p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Предпросмотр</h3>

            <!-- Карточка в сайдбаре (тёмный фон) -->
            <div class="bg-gradient-to-b from-[#0f172a] to-[#1e293b] rounded-xl p-4">
              <p class="text-xs text-white/40 mb-2">Вид в сайдбаре</p>
              <div
                class="transition-all duration-200"
                :class="cardEffectClass(cfg)"
                :style="cardConfigToStyle(cfg)"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="shrink-0 rounded-full bg-white/30 flex items-center justify-center text-sm font-bold"
                    :style="{ ...cardAvatarStyle(cfg), backgroundColor: 'rgba(255,255,255,0.25)' }"
                  >
                    <span :style="cardNameStyle(cfg)">И</span>
                  </div>
                  <div class="min-w-0">
                    <div class="truncate" :style="cardNameStyle(cfg)">Иван Иванов</div>
                    <div class="truncate" :style="cardStatusStyle(cfg)">Тимейкер</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Карточка в расписании -->
            <div class="bg-white rounded-xl p-4 border border-gray-200">
              <p class="text-xs text-gray-400 mb-2">Вид в графике смен</p>
              <div
                class="transition-all duration-200 w-full"
                :class="cardEffectClass(cfg)"
                :style="cardConfigToStyle(cfg)"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="shrink-0 rounded-full bg-white/30 flex items-center justify-center text-xs font-bold"
                    :style="{ ...cardAvatarStyle(cfg), width: '32px', height: '32px', backgroundColor: 'rgba(255,255,255,0.25)' }"
                  >
                    <span :style="{ ...cardNameStyle(cfg), fontSize: '12px' }">И</span>
                  </div>
                  <div class="min-w-0">
                    <div class="truncate" :style="{ ...cardNameStyle(cfg), fontSize: '13px' }">Иванов И.</div>
                    <div class="truncate" :style="{ ...cardStatusStyle(cfg), fontSize: '11px' }">10:00–22:00</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Карточка на странице профиля -->
            <div class="bg-white rounded-xl p-4 border border-gray-200">
              <p class="text-xs text-gray-400 mb-2">Превью в профиле</p>
              <div
                class="transition-all duration-200 w-32 h-16 flex items-center justify-center text-center"
                :class="cardEffectClass(cfg)"
                :style="{ ...cardConfigToStyle(cfg), padding: '6px 10px' }"
              >
                <div>
                  <div class="truncate" :style="{ ...cardNameStyle(cfg), fontSize: '11px' }">{{ editingItem.name || 'Новый' }}</div>
                  <div class="truncate" :style="{ ...cardStatusStyle(cfg), fontSize: '9px' }">preview</div>
                </div>
              </div>
            </div>
          </div>

          <!-- JSON-превью для отладки -->
          <details class="text-xs text-gray-400">
            <summary class="cursor-pointer hover:text-gray-600">JSON конфиг</summary>
            <pre class="mt-2 bg-gray-900 text-green-300 p-3 rounded-lg overflow-auto max-h-60 text-[10px]">{{ JSON.stringify(cfg, null, 2) }}</pre>
          </details>
        </div>
      </div>
    </div>

    <!-- Модалка создания -->
    <div v-if="showCreateModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40" @click.self="showCreateModal = false">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Новый шаблон карточки</h3>
        <div class="space-y-3">
          <div>
            <label class="field-label">Название</label>
            <input v-model="newName" class="field-input" placeholder="Gold Premium" />
          </div>
          <div>
            <label class="field-label">Код</label>
            <input v-model="newCode" class="field-input" placeholder="gold_premium" />
          </div>
        </div>
        <div class="flex gap-3 mt-5">
          <button @click="createAndEdit" :disabled="!newName || !newCode" class="btn-primary flex-1">Создать и настроить</button>
          <button @click="showCreateModal = false" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Отмена</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { api } from '@/api/client'
import {
  cardConfigToStyle,
  cardNameStyle,
  cardStatusStyle,
  cardAvatarStyle,
  cardEffectClass,
  defaultStyleConfig,
  effectOptions,
  gradientPresets,
  fontFamilyOptions,
} from '@/utils/cardStyleRenderer'

const loading = ref(true)
const error = ref('')
const items = ref([])
const editingItem = ref(null)
const isNew = ref(false)
const saving = ref(false)
const saveMsg = ref('')
const saveError = ref('')
const uploadingBg = ref(false)
const showCreateModal = ref(false)
const newName = ref('')
const newCode = ref('')

function cloneDeep(obj) {
  return JSON.parse(JSON.stringify(obj))
}

const cfg = reactive(cloneDeep(defaultStyleConfig))

function deepMergeCfg(target, source) {
  for (const key of Object.keys(target)) {
    if (source && key in source) {
      if (typeof target[key] === 'object' && target[key] !== null && !Array.isArray(target[key])) {
        deepMergeCfg(target[key], source[key])
      } else {
        target[key] = cloneDeep(source[key])
      }
    }
  }
  if (source?.bg?.gradient?.stops) {
    target.bg.gradient.stops = cloneDeep(source.bg.gradient.stops)
  }
}

async function loadItems() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.admin.profileCards()
    items.value = res.items || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

function startEdit(item) {
  editingItem.value = { ...item }
  isNew.value = false
  saveMsg.value = ''
  saveError.value = ''
  resetCfg(item.styleConfig)
}

function cancelEdit() {
  editingItem.value = null
  isNew.value = false
}

function resetCfg(source) {
  const def = cloneDeep(defaultStyleConfig)
  Object.assign(cfg, def)
  if (source && typeof source === 'object' && Object.keys(source).length) {
    deepMergeCfg(cfg, source)
  }
}

function createAndEdit() {
  editingItem.value = {
    id: null,
    name: newName.value,
    code: newCode.value,
    description: '',
    cssClass: '',
    isActive: true,
    styleConfig: {},
  }
  isNew.value = true
  resetCfg({})
  showCreateModal.value = false
  newName.value = ''
  newCode.value = ''
}

async function saveItem() {
  saving.value = true
  saveMsg.value = ''
  saveError.value = ''
  try {
    const body = {
      name: editingItem.value.name,
      code: editingItem.value.code,
      description: editingItem.value.description,
      cssClass: editingItem.value.cssClass || `profile-card-${editingItem.value.code}`,
      isActive: editingItem.value.isActive ? 1 : 0,
      styleConfig: cloneDeep(cfg),
    }
    let res
    if (isNew.value) {
      res = await api.admin.createProfileCard(body)
    } else {
      res = await api.admin.updateProfileCard(editingItem.value.id, body)
    }
    saveMsg.value = res.message || 'Сохранено!'
    if (res.item) {
      editingItem.value = { ...res.item }
      isNew.value = false
    }
    await loadItems()
  } catch (e) {
    saveError.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}

async function confirmDelete(item) {
  if (!confirm(`Удалить шаблон «${item.name}»? Это действие необратимо.`)) return
  try {
    await api.admin.deleteProfileCard(item.id)
    await loadItems()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка удаления'
  }
}

async function duplicateItem(item) {
  try {
    const body = {
      name: item.name + ' (копия)',
      code: item.code + '_copy_' + Date.now(),
      description: item.description,
      cssClass: '',
      isActive: 0,
      styleConfig: item.styleConfig || {},
    }
    const res = await api.admin.createProfileCard(body)
    await loadItems()
    if (res.item) startEdit(res.item)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка дублирования'
  }
}

async function uploadBgImage(ev) {
  const file = ev.target.files?.[0]
  if (!file) return
  uploadingBg.value = true
  try {
    const res = await api.admin.uploadProfileCardBg(file)
    if (res.url) {
      cfg.bg.image.url = res.url
      cfg.bg.type = 'image'
    }
  } catch (e) {
    saveError.value = e.data?.message || e.message || 'Ошибка загрузки изображения'
  } finally {
    uploadingBg.value = false
    ev.target.value = ''
  }
}

function applyGradientPreset(preset) {
  cfg.bg.gradient.stops = cloneDeep(preset.stops)
}

function addGradientStop() {
  const last = cfg.bg.gradient.stops[cfg.bg.gradient.stops.length - 1]
  cfg.bg.gradient.stops.push({ color: last?.color || '#ffffff', pos: Math.min((last?.pos ?? 50) + 25, 100) })
}

function presetGradientCss(p) {
  const stops = p.stops.map(s => `${s.color} ${s.pos}%`).join(', ')
  return `linear-gradient(135deg, ${stops})`
}

function toHex(val) {
  if (!val || val.startsWith('#')) return val || '#000000'
  const m = val.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/)
  if (!m) return '#000000'
  return '#' + [m[1], m[2], m[3]].map(n => parseInt(n).toString(16).padStart(2, '0')).join('')
}

onMounted(loadItems)
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 rounded-lg bg-primary text-white font-medium hover:bg-primary-dark disabled:opacity-50;
}
.card-section {
  @apply bg-white rounded-xl border border-gray-200 p-5 shadow-sm;
}
.section-title {
  @apply text-sm font-semibold text-gray-700 mb-3;
}
.field-label {
  @apply block text-xs font-medium text-gray-500 mb-1;
}
.field-input {
  @apply w-full h-9 px-3 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary;
}
.color-picker {
  @apply w-9 h-9 rounded-lg border border-gray-300 cursor-pointer shrink-0 p-0.5;
}

/* Card effects — GPU-accelerated, CSS-only */
:deep(.card-effect-shimmer) {
  position: relative;
  overflow: hidden;
}
:deep(.card-effect-shimmer)::after {
  content: '';
  position: absolute;
  top: 0; left: -100%; width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  animation: shimmer 2.5s ease-in-out infinite;
}
@keyframes shimmer {
  0% { left: -100%; }
  100% { left: 200%; }
}

:deep(.card-effect-glow) {
  animation: glow 2s ease-in-out infinite alternate;
}
@keyframes glow {
  from { filter: brightness(1); }
  to { filter: brightness(1.15); }
}

:deep(.card-effect-pulse) {
  animation: pulse-card 2s ease-in-out infinite;
}
@keyframes pulse-card {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.015); }
}

:deep(.card-effect-shine) {
  position: relative;
  overflow: hidden;
}
:deep(.card-effect-shine)::after {
  content: '';
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.08), transparent 30%);
  animation: shine-rotate 4s linear infinite;
}
@keyframes shine-rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
