<template>
  <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
    <template slot="field">
      <div class="flex flex-row items-center">
        <div v-if="value" class="icon-preview mr-5">
          <i :class="value"></i>
        </div>
        <div class="flex justify-center items-center">
          <button v-if="value" class="btn btn-default btn-primary  mr-3" @click="clear">
            Clear Icon
          </button>
          <button class="btn btn-default btn-primary" @click.prevent="toggleModal">
            {{ openModalText }}
          </button>
          <button
            v-if="field.editor"
            class="btn btn-default btn-primary ml-3"
            @click.prevent="toggleEditor"
          >
            {{ editButtonText }}
          </button>
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

      <modal v-if="modalOpened" @close="closeModal" class="heroicon-modal">
        <div class="bg-white rounded-lg shadow-lg">
          <div class="px-8 py-6 border-b relative" style="border-color: #e0e0e0">
            <heading :level="2" class="mb-0 px-10">{{ __('Select Icon') }}</heading>
            <a href="#" class="heroicon-close" @click.prevent="closeModal">
              <svg
                class="w-10 h-10"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </a>
          </div>
          <div class="px-8 py-4 border-b">
            <div class="flex flex-wrap -mx-4">
              <div class="w-1/3 px-4">
                <select
                  id="type"
                  class="w-full form-control form-select"
                  v-model="filter.type"
                  :disabled="disableOptions"
                >
                  <option v-for="opt in iconOptions" :value="opt.value" :key="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
              <div class="w-2/3 px-4">
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
          <div class="px-8 py-6 heroicon-inner" @scroll="onScroll">
            <div class="grid-container">
              <div
                v-for="icon in testIcons"
                :key="`${icon.type}_${icon.name}`"
                class="
                  flex flex-col flex-1
                  items-center
                  justify-center
                  text-center
                  cursor-pointer
                "
                @click="saveIcon(icon)"
              >
                <div v-html="icon.content" class="w-12 h-12 icon-container"></div>
                <div>{{ icon.name }}</div>
              </div>
            </div>
          </div>
        </div>
      </modal>
    </template>
  </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';

export default {
  mixins: [FormField, HandlesValidationErrors],

  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      defaultIcons: [],
      modalOpened: false,
      editorOpened: false,
      value: '',
      filter: {
        type: ''
      },
      chunk : 0,
      items : [],
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
      this.value = `${icon.type} fa-${icon.name}`;
      this.search = '';
      this.closeModal();
    },
    onScroll ({ target: { scrollTop, clientHeight, scrollHeight }}) {
      if ((scrollTop + clientHeight >= (scrollHeight - 250)) && this.expanded === false) {
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
    }
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
      return allIcons.filter((icon) => enabledTypes.includes(icon.type));
    },
    filteredIcons() {
      let filteredIcons = this.icons;
      if (this.filter.type) {
        filteredIcons = filteredIcons.filter((icon) => icon.type === this.filter.type);
      }

      if (this.search) {
        filteredIcons = filteredIcons.filter((icon) => icon.name.includes(this.search));
      }
      return filteredIcons;
    },
    search: {
        get() {
          return this.debouncedSearch
        },
        set(val) {
          if (this.timeout) clearTimeout(this.timeout)
          this.timeout = setTimeout(() => {
            this.debouncedSearch = val
          }, 300)
        }
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
      }
    }
  },
  created() {
    const escapeHandler = (e) => {
      if (e.key === 'Escape' && this.modalOpened) {
        this.closeModal();
      }
    };
    document.addEventListener('keydown', escapeHandler);
    this.$once('hook:destroyed', () => {
      document.removeEventListener('keydown', escapeHandler);
    });
    this.filter.type = this.iconOptions[0].value;
    this.getChunk();
  },
};
</script>
<style>
.icon-preview svg {
  width: 60px;
  height: 60px;
}

.icon-preview {
  font-size:3em;
  margin-right:1.6rem;
  display:flex;
  flex-direction:column;
  justify-content:center;
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
  flex-basis: 0;
  height: 100%;
  flex-direction: column;
}

.heroicon-modal > div:first-child > div {
  position: relative;
  max-height: 80%;
  overflow: hidden;
  width: 60%;
  margin: 0 auto;
  display: flex;
  flex-grow: 1;
}

.heroicon-modal > div:first-child > div > div {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.heroicon-inner {
  height: 80%;
  overflow-y: scroll;
  overflow-x: hidden;
}

.heroicon-close {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  right: 1.5rem;
  font-size: 1.5rem;
  color: #3c4b5f;
}

.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    grid-gap: 1rem;
}


.grid-container > div {
  height:6rem;
}

.grid-container > div > .icon-container  {
  font-size:2rem;
}

@media (max-width: 992px) {
  div.modal.heroicon-modal {
    top:0;
  }
}
@media (max-width: 640px) {
  .heroicon-modal > div:first-child > div {
    width:80%;
  }
}
</style>
