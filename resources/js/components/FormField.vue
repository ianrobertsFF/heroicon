<template>
  <DefaultField :field="currentField" :errors="errors" :show-help-text="showHelpText">
    <template #field>
      <div class="flex flex-row items-center justify-center flex-wrap ffc-gap-3">
        <div v-if="value" class="icon-preview">
          <i :class="value"></i>
        </div>
        <div
          class="
            flex
            ffc-flex-col ffc-flex-grow
            sm:ffc-flex-row
            justify-center
            items-center
            ffc-gap-2 ffc-justify-start ffc-items-start
          "
        >
          <DefaultButton
            @click.prevent="toggleModal"
            class="
              ffc-flex-grow
              sm:ffc-grow-0
              w-full
              ffc-max-w-[250px]
              sm:ffc-max-w-fit
              ffc-toggle-button
            "
          >
            {{ openModalText }}
          </DefaultButton>
          <DefaultButton
            v-if="value"
            @click.prevent="clear"
            class="mr-2 ffc-flex-grow sm:ffc-grow-0 w-full ffc-max-w-[250px] sm:ffc-max-w-fit"
          >
            Clear Icon
          </DefaultButton>
          <DefaultButton v-if="field.editor" @click.prevent="toggleEditor">
            {{ editButtonText }}
          </DefaultButton>
        </div>
      </div>
      <transition name="fade">
        <textarea
          v-show="editorOpened"
          :id="field.name"
          type="text"
          class="w-full form-control form-input form-input-bordered h-36"
          :class="errorClasses"
          :placeholder="field.name"
          v-model="value"
        />
      </transition>

      <Modal :show="modalOpened" class="h-full fontawesome-modal" maxWidth="2xl">
        <div class="bg-white rounded-lg shadow-lg h-full fontawesome-modal-inner">
          <div class="px-8 py-6 border-b relative" style="border-color: #e0e0e0">
            <heading :level="2" class="mb-0 px-10">{{ __('Select Icon') }}</heading>
            <a href="#" class="heroicon-close" @click.prevent="closeModal">
              <i class="far fa-times"></i>
            </a>
          </div>
          <div class="ffc-px-4 ffc-py-2 md:ffc-px-8 md:ffc-py-4 border-b">
            <div class="flex flex-wrap ffc-gap-2 -mx-4">
              <div class="grow">
                <div class="relative w-full">
                  <select
                    id="type"
                    class="w-full form-control form-select form-select-bordered"
                    v-model="filter.type"
                    :disabled="disableOptions"
                  >
                    <option v-for="opt in iconOptions" :value="opt.value" :key="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                  <IconArrow class="pointer-events-none form-select-arrow" />
                </div>
              </div>
              <div class="grow">
                <input
                  type="text"
                  id="search"
                  class="w-full form-control form-input form-input-bordered"
                  placeholder="Search icons"
                  v-model="search"
                  @keypress.enter.prevent
                />
              </div>
            </div>
          </div>
          <div class="ffc-px-4 ffc-py-2 md:ffc-px-8 md:ffc-py-4 heroicon-inner" @scroll="onScroll">
            <div class="grid-container">
              <div
                v-for="icon in testIcons"
                :key="`${icon.type}_${icon.value}`"
                class="flex flex-col flex-1 items-center justify-center text-center cursor-pointer"
                @click="saveIcon(icon)"
              >
                <div class="w-12 h-12 icon-container">
                  <i :class="`${filter.type} ${icon.value}`"></i>
                </div>
                <div>{{ icon.label }}</div>
              </div>
            </div>
          </div>
        </div>
      </Modal>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from 'laravel-nova';

export default {
  name: 'Heroicon',
  mixins: [DependentFormField, HandlesValidationErrors],
  mounted() {
    console.log('FieldMounted');
    console.log(this);
  },
  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      defaultIcons: [],
      modalOpened: false,
      editorOpened: false,
      value: '',
      filter: {
        type: '',
        previousType: '',
        checkedType: '',
      },
      chunk: 0,
      items: [],
      expanded: false,
      debouncedSearch: '',
      timeout: null,
    };
  },
  methods: {
    setInitialValue() {
      this.value = this.field.value || '';
    },
    fill(formData) {
      formData.append(this.field.attribute, this.value || '');
    },
    clear() {
      this.value = '';
    },
    toggleModal() {
      console.log('modalClick');
      this.modalOpened = !this.modalOpened;
    },
    toggleEditor() {
      this.editorOpened = !this.editorOpened;
    },
    closeModal() {
      this.modalOpened = false;
      this.chunk = 0;
      this.expanded = false;
      this.items = [];
      this.getChunk();
    },
    saveIcon(icon) {
      this.value = `${this.filter.type} ${icon.value}`;
      this.search = '';
      this.closeModal();
    },
    onScroll({ target: { scrollTop, clientHeight, scrollHeight } }) {
      if (scrollTop + clientHeight >= scrollHeight - 250 && this.expanded === false) {
        this.expanded = true;
        this.getChunk();
      }
    },
    getChunk() {
      let chunkSize = 250;
      let nextChunk = this.filteredIcons.slice(this.chunk, this.chunk + chunkSize);
      this.items = [...this.items, ...nextChunk];
      this.expanded = false;
      this.chunk += chunkSize;
    },
    findIcon(testArray, search) {
      search = search.toLowerCase();
      return testArray.filter((icon) => {
        let labelTest = icon.label.toLowerCase().includes(search);
        let valueTest = icon.value.toLowerCase().includes(search);
        let searchTest = icon.search
          ? icon.search.some((searchArray) => searchArray.includes(search))
          : false;
        return searchTest || labelTest || valueTest;
      });
    },
  },
  computed: {
    icons() {
      let allIcons = this.defaultIcons;
      const enabledTypes = [];
      this.field.icons.forEach((iconSet) => {
        enabledTypes.push(iconSet.value);
        if (typeof iconSet.icons !== 'undefined') {
          allIcons = [...allIcons, ...iconSet.icons];
        }
      });
      return allIcons;
    },
    filterPreCheck() {
      let iconSet = this.field.icons.find((set) => set.value === this.filter.type);
      if (typeof iconSet.icons === 'undefined' && typeof iconSet.subType !== 'undefined') {
        if (iconSet.subType !== this.filter.previousType) {
          this.filter.checkedType = iconSet.subType;
          this.filter.previousType = iconSet.subType;
        }
      } else {
        this.filter.checkedType = this.filter.type;
        this.filter.previousType = this.filter.type;
      }
      return this.filteredIcons;
    },
    filteredIcons() {
      let filteredIcons = this.icons;
      if (this.filter.checkedType) {
        filteredIcons = filteredIcons.filter((icon) => icon.type === this.filter.checkedType);
      }

      if (this.search) {
        filteredIcons = this.findIcon(filteredIcons, this.search);
      }
      return filteredIcons;
    },
    search: {
      get() {
        return this.debouncedSearch;
      },
      set(val) {
        if (this.timeout) clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
          this.debouncedSearch = val;
        }, 300);
      },
    },
    testIcons() {
      return this.items;
    },
    editButtonText() {
      if (this.editorOpened) {
        return 'Close';
      }
      return 'Edit';
    },
    openModalText() {
      if (this.value) {
        return 'Change icon';
      }
      return 'Add icon';
    },
    iconOptions() {
      if (this.field.icons.length > 1) {
        return [...this.field.icons];
      }
      return this.field.icons;
    },
    disableOptions() {
      return this.field.icons.length === 1;
    },
  },
  watch: {
    filteredIcons: {
      // This will let Vue know to look inside the array
      deep: true,

      // We have to move our method to a handler field
      handler() {
        this.chunk = 0;
        this.items = [];
        this.getChunk();
      },
    },
    'filter.type'(newValue) {
      this.filterPreCheck;
    },
  },
  created() {
    console.log('FieldCreated');
    const escapeHandler = (e) => {
      if (e.key === 'Escape' && this.modalOpened) {
        this.closeModal();
      }
    };
    document.addEventListener('keydown', escapeHandler);
    this.filter.type = this.iconOptions[0].value;
    this.getChunk();
  },

  beforeDestroy() {
    document.removeEventListener('keydown', escapeHandler);
  },
};
</script>
<style>
/*** 

  New CSS

***/
@media (max-width: 768px) {
  .ffc-toggle-button:only-child {
    max-width: unset;
  }
}
.fontawesome-modal {
  padding-top: 12vh;
  padding-bottom: 12vh;
}

.fontawesome-modal-inner {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/*** 

Old CSS

***/

.icon-preview svg {
  width: 60px;
  height: 60px;
}

.icon-preview {
  font-size: 3.5em;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.icon-container > svg {
  max-height: 100%;
  max-width: 100%;
  padding-bottom: 10px;
}

.icon-preview:hover .close-icon {
  visibility: visible;
}

.close-icon {
  transform: translate(50%, -50%);
  opacity: 0.75;
  transition: visibility 0.4s linear;
}

.close-icon:hover {
  opacity: 1;
}

.close-icon svg {
  width: 30px;
  height: 30px;
}

.heroicon-modal > div:first-child {
  height: 100%;
  align-items: center;
}

.heroicon-modal > div:first-child > div {
  position: relative;
  max-height: 80%;
  width: 60%;
  height: 100%;
}

.heroicon-modal > div:first-child > div > div {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.heroicon-inner {
  height: 80%;
  overflow-y: scroll;
  overflow-x: hidden;
}

.heroicon-close {
  position: absolute;
  top: 0.7rem;
  right: 0.7rem;
  font-size: 1.3rem;
  padding: 5px 8px;
  color: #3c4b5f;
}

.grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  grid-gap: 1rem;
}

.grid-container > div {
  height: 6rem;
}

.grid-container > div > .icon-container {
  font-size: 2rem;
}

@media (max-width: 992px) {
  div.modal.heroicon-modal {
    top: 0;
  }
}
@media (max-width: 640px) {
  .heroicon-modal > div:first-child > div {
    width: 80%;
  }
}

/** */
</style>
