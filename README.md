# Denkon Image Gallery — Free

This is the public, editable source repository for the free **Denkon Image Gallery** WordPress plugin.

## Development and build

The distributed plugin files are the source files. No bundler, transpiler, minifier, or external build step is required.

- Gutenberg block source: `blocks/gallery/index.js`
- Front-end scripts: `assets/js/`
- Admin scripts: `assets/js/admin.js`
- PHP source: `denkon-image-gallery.php` and `includes/`

The plugin is licensed under GPL-2.0-or-later; see the header in `denkon-image-gallery.php` and `readme.txt`.

Лёгкий плагин галерей Denkon. Галереи используют стандартную медиатеку WordPress: плагин не создаёт собственных таблиц, не хранит копии файлов и не подключает сторонние библиотеки на публичной части сайта.

## Что включено в 0.2.0

- неограниченное количество галерей;
- выбор изображений из медиатеки, ручная сортировка и удаление из галереи;
- динамический Gutenberg-блок **Denkon Image Gallery**;
- шорткод `[denkon_gallery id="123"]`;
- адаптивные макеты `grid` и `masonry`: по умолчанию `grid` использует одинаковые карточки 3:2 и обрезает края через `object-fit: cover`, поэтому разная ориентация файлов не ломает ряд;
- нативный доступный lightbox с управлением клавиатурой;
- `loading="lazy"`, `decoding="async"` и адаптивные изображения WordPress;
- подписи из Caption вложений WordPress.
- премиальный Denkon Studio в редакторе галереи: изображения, параметры отображения и подсказки собраны в одном интерфейсе;
- базовые настройки галереи (макет, число колонок, соотношение сторон, расстояние, размер WordPress, lightbox, подписи);
- локализация интерфейса администратора: English по умолчанию, Russian для русской локали пользователя;
- приоритет: явные параметры Gutenberg-блока → настройки галереи → безопасные значения по умолчанию.

## Шорткод

```text
[denkon_gallery id="123" layout="grid" columns="3" ratio="3 / 2" gap="16" size="large" per_page="9" lightbox="true" captions="true"]
```

| Параметр | Значения | По умолчанию |
| --- | --- | --- |
| `id` | ID галереи | обязательно |
| `layout` | `grid`, `masonry` | настройка галереи |
| `columns` | от 1 до 6 | настройка галереи |
| `size` | `full` или зарегистрированный размер WordPress | настройка галереи |
| `lightbox` | `true`, `false` | настройка галереи |
| `captions` | `true`, `false` | настройка галереи |
| `ratio` | `1 / 1`, `4 / 3`, `3 / 2`, `16 / 9` | настройка галереи |
| `gap` | от 0 до 48, px | настройка галереи |
| `per_page` | число изображений на странице, `0` — все | настройка галереи |

Каждый явно указанный атрибут шорткода имеет приоритет над настройкой самой галереи. У Gutenberg-блока те же override-поля; вариант `Use gallery default` оставляет настройку галереи без изменений.

`masonry` намеренно сохраняет исходные пропорции изображений и даёт неровные по высоте колонки. Для ровной коммерческой витрины используйте стандартный `grid`.

## Как использовать

1. Активируйте плагин.
2. В меню «Галереи» создайте галерею, выберите изображения и сохраните её.
3. Добавьте блок **Denkon Image Gallery** в редакторе либо вставьте шорткод с ID созданной галереи.

## Принципы бесплатной версии

Данные остаются переносимыми: изображения — обычные `attachment`, состав галереи — метаполе записи `denkon_gallery`. Доступ к редактированию обеспечивают стандартные возможности WordPress (`edit_post`) и nonce. В планы следующих релизов можно вынести альбомы, теги, импорт и расширенные режимы вывода, не меняя эту модель хранения.
