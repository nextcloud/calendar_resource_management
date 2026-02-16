
<template>
  <div class="section">
    <h2>Ressourcen- und Raumverwaltung</h2>

    <NcFormGroup label="Gebäude">
      <NcTextField v-model="newBuildingName" label="Gebäudename" placeholder="Pflichtfeld" />
      <NcTextField v-model="newBuildingAddress" label="Adresse" />
      <NcButton type="primary" @click="addBuilding">Hinzufügen</NcButton>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Adresse</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="building in buildings" :key="building.id">
            <td>{{ building.name }}</td>
            <td>{{ building.address || 'Keine Adresse' }}</td>
            <td><NcButton type="error" size="small" @click="deleteBuilding(building.id)">Löschen</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup label="Stockwerke">
      <NcTextField v-model="newStoryName" label="Stockwerkname" placeholder="Pflichtfeld" />
      <NcSelect 
        v-model="selectedBuildingForStory" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        input-label="Gebäude"
        placeholder="Bitte auswählen"
      />
      <NcButton type="primary" @click="addStory">Hinzufügen</NcButton>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Gebäude</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="story in stories" :key="story.id">
            <td>{{ story.name }}</td>
            <td>{{ getBuildingName(story.buildingId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteStory(story.id)">Löschen</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup label="Räume">
      <NcTextField v-model="newRoomName" label="Raumname" placeholder="Pflichtfeld" />
      <NcTextField v-model="newRoomEmail" label="E-Mail" type="email" />
      <NcTextField v-model="newRoomType" label="Raumtyp" placeholder="z.B. meeting-room" />
      <NcTextField v-model="newRoomNumber" label="Raumnummer" placeholder="z.B. 1.23" />
      <NcTextField v-model="newRoomContactPerson" label="Ansprechpartner (User-ID)" placeholder="z.B. admin" />
      
      <div class="input-field">
        <label for="room-capacity">Kapazität (Personen)</label>
        <input 
          id="room-capacity"
          v-model.number="newRoomCapacity" 
          type="number" 
          min="0"
          placeholder="z.B. 10" 
          class="capacity-input"
        />
      </div>
      
      <NcSelect 
        v-model="selectedBuildingForRoom" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        input-label="Gebäude"
        placeholder="Bitte auswählen"
        @update:modelValue="onBuildingSelectedForRoom"
      />
      
      <NcSelect 
        v-model="selectedStory" 
        :options="filteredStoryOptions"
        label-key="label"
        value-key="id"
        input-label="Stockwerk"
        placeholder="Zuerst Gebäude wählen"
        :disabled="!selectedBuildingForRoom"
      />
      
      <div class="checkbox-group">
        <h4>Ausstattung</h4>
        <NcCheckboxRadioSwitch v-model="newRoomHasPhone" type="switch">Telefon</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasVideo" type="switch">Videokonferenz</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasTv" type="switch">TV/Monitor</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasProjector" type="switch">Projektor</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasWhiteboard" type="switch">Whiteboard</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomWheelchairAccessible" type="switch">Rollstuhlgerecht</NcCheckboxRadioSwitch>
      </div>
      
      <NcButton type="primary" @click="addRoom">Hinzufügen</NcButton>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>E-Mail</th>
            <th>Typ</th>
            <th>Raum-Nr</th>
            <th>Kapazität</th>
            <th>Ausstattung</th>
            <th>Gebäude</th>
            <th>Stockwerk</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in rooms" :key="room.id">
            <td>{{ room.name }}</td>
            <td>{{ room.email || '-' }}</td>
            <td>{{ room.roomType || 'default' }}</td>
            <td>{{ room.roomNumber || '-' }}</td>
            <td>{{ room.capacity || '-' }}</td>
            <td>
              <span v-if="room.hasPhone" title="Telefon">📞 </span>
              <span v-if="room.hasVideoConferencing" title="Videokonferenz">📹 </span>
              <span v-if="room.hasTv" title="TV">📺 </span>
              <span v-if="room.hasProjector" title="Projektor">📽️ </span>
              <span v-if="room.hasWhiteboard" title="Whiteboard">📋 </span>
              <span v-if="room.isWheelchairAccessible" title="Rollstuhlgerecht">♿ </span>
            </td>
            <td>{{ getBuildingNameForRoom(room.storyId) }}</td>
            <td>{{ getStoryName(room.storyId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteRoom(room.id)">Löschen</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup label="Ressourcen">
      <NcTextField v-model="newResourceName" label="Ressourcenname" placeholder="Pflichtfeld" />
      <NcTextField v-model="newResourceEmail" label="E-Mail" type="email" />
      <NcTextField v-model="newResourceType" label="Ressourcentyp" />
      <NcSelect 
        v-model="selectedBuilding" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        input-label="Gebäude"
        placeholder="Bitte auswählen"
      />
      <NcButton type="primary" @click="addResource">Hinzufügen</NcButton>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>E-Mail</th>
            <th>Typ</th>
            <th>Gebäude</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="resource in resources" :key="resource.id">
            <td>{{ resource.name }}</td>
            <td>{{ resource.email || 'Keine E-Mail' }}</td>
            <td>{{ resource.resourceType || 'default' }}</td>
            <td>{{ getBuildingName(resource.buildingId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteResource(resource.id)">Löschen</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>
  </div>
</template>

<script>
import { toRaw } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

export default {
  name: 'CalendarResourceAdmin',
  components: {
    NcTextField,
    NcButton,
    NcSelect,
    NcFormGroup,
    NcCheckboxRadioSwitch
  },
  data() {
    return {
      buildings: [],
      stories: [],
      rooms: [],
      resources: [],
      buildingOptions: [],
      storyOptions: [],
      newBuildingName: '',
      newBuildingAddress: '',
      newStoryName: '',
      selectedBuildingForStory: null,
      selectedBuildingForRoom: null,
      newRoomName: '',
      newRoomEmail: '',
      newRoomType: 'default',
      newRoomNumber: '',
      newRoomContactPerson: '',
      newRoomCapacity: null,
      newRoomHasPhone: false,
      newRoomHasVideo: false,
      newRoomHasTv: false,
      newRoomHasProjector: false,
      newRoomHasWhiteboard: false,
      newRoomWheelchairAccessible: false,
      selectedStory: null,
      newResourceName: '',
      newResourceEmail: '',
      newResourceType: 'default',
      selectedBuilding: null
    }
  },
  computed: {
    filteredStoryOptions() {
      if (!this.selectedBuildingForRoom) {
        return [];
      }
      const buildingId = typeof this.selectedBuildingForRoom === 'object' 
        ? this.selectedBuildingForRoom.id 
        : this.selectedBuildingForRoom;
      
      return this.stories
        .filter(story => story.buildingId === buildingId)
        .map(story => ({
          id: story.id,
          label: story.name
        }));
    }
  },
  mounted() {
    this.loadBuildings();
    this.loadStories();
    this.loadRooms();
    this.loadResources();
  },
  methods: {
    onBuildingSelectedForRoom() {
      // Reset story selection when building changes
      this.selectedStory = null;
    },
    async loadBuildings() {
      try {
        const res = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/buildings'), {
          headers: { 'requesttoken': OC.requestToken }
        });
        const data = await res.json();
        this.buildings = data;
        // Manually update buildingOptions
        this.buildingOptions = data.map(building => ({
          id: building.id,
          label: building.name
        }));
        console.log('Buildings loaded:', this.buildings);
        console.log('Building options:', JSON.parse(JSON.stringify(this.buildingOptions)));
      } catch (error) {
        console.error('Error loading buildings:', error);
      }
    },
    async addBuilding() {
      if (!this.newBuildingName) {
        alert('Bitte geben Sie einen Gebäudenamen ein!');
        return;
      }
      
      const response = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/buildings'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
        body: JSON.stringify({ 
          name: this.newBuildingName, 
          address: this.newBuildingAddress 
        })
      });
      this.newBuildingName = '';
      this.newBuildingAddress = '';
      // Wait for buildings to reload before continuing
      await this.loadBuildings();
    },
    async loadStories() {
      try {
        const res = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/stories'), {
          headers: { 'requesttoken': OC.requestToken }
        });
        const data = await res.json();
        this.stories = data;
        // Manually update storyOptions
        this.storyOptions = data.map(story => ({
          id: story.id,
          label: story.name
        }));
        console.log('Stories loaded:', this.stories);
        console.log('Story options:', this.storyOptions);
      } catch (error) {
        console.error('Error loading stories:', error);
      }
    },
    async addStory() {
      if (!this.newStoryName) {
        alert('Bitte geben Sie einen Stockwerksnamen ein!');
        return;
      }
      
      if (!this.selectedBuildingForStory) {
        alert('Bitte wählen Sie zuerst ein Gebäude aus!');
        return;
      }
      // Extract the ID from the selected object
      const buildingId = typeof this.selectedBuildingForStory === 'object' 
        ? this.selectedBuildingForStory.id 
        : this.selectedBuildingForStory;
      
      console.log('Creating story with buildingId:', buildingId);
      console.log('Available buildings:', JSON.parse(JSON.stringify(this.buildings)));
      console.log('Building options:', JSON.parse(JSON.stringify(this.buildingOptions)));
      try {
        const response = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/stories'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
          body: JSON.stringify({ 
            name: this.newStoryName, 
            buildingId: buildingId 
          })
        });
        const result = await response.json();
        if (!result.success) {
          alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
          return;
        }
        this.newStoryName = '';
        this.selectedBuildingForStory = null;
        this.loadStories();
      } catch (error) {
        console.error('Error adding story:', error);
        alert('Fehler beim Erstellen des Stockwerks');
      }
    },
    async deleteBuilding(id) {
      if (!confirm('Möchten Sie dieses Gebäude wirklich löschen?')) {
        return;
      }
      await fetch(OC.generateUrl(`/apps/calendar_resource_management/admin/buildings/${id}`), {
        method: 'DELETE',
        headers: { 'requesttoken': OC.requestToken }
      });
      // Reset selected building if it was deleted
      const selectedBuildingId = typeof this.selectedBuildingForStory === 'object' 
        ? this.selectedBuildingForStory.id 
        : this.selectedBuildingForStory;
      const selectedResourceBuildingId = typeof this.selectedBuilding === 'object' 
        ? this.selectedBuilding.id 
        : this.selectedBuilding;
        
      if (selectedBuildingId === id) {
        this.selectedBuildingForStory = null;
      }
      if (selectedResourceBuildingId === id) {
        this.selectedBuilding = null;
      }
      // Wait for reloads to complete
      await this.loadBuildings();
      await this.loadStories(); // Also reload stories as they might be affected
    },
    async deleteStory(id) {
      if (!confirm('Möchten Sie dieses Stockwerk wirklich löschen?')) {
        return;
      }
      await fetch(OC.generateUrl(`/apps/calendar_resource_management/admin/stories/${id}`), {
        method: 'DELETE',
        headers: { 'requesttoken': OC.requestToken }
      });
      this.loadStories();
    },
    async loadRooms() {
      const res = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/rooms'), {
        headers: { 'requesttoken': OC.requestToken }
      });
      this.rooms = await res.json();
    },
    async addRoom() {
      // Validation
      if (!this.newRoomName) {
        alert('Bitte geben Sie einen Raumnamen ein!');
        return;
      }
      if (!this.selectedStory) {
        alert('Bitte wählen Sie ein Gebäude und Stockwerk aus!');
        return;
      }
      
      // Extract the ID from the selected object
      const storyId = typeof this.selectedStory === 'object' 
        ? this.selectedStory.id 
        : this.selectedStory;
        
      await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/rooms'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
        body: JSON.stringify({ 
          name: this.newRoomName, 
          email: this.newRoomEmail, 
          roomType: this.newRoomType,
          roomNumber: this.newRoomNumber,
          contactPersonUserId: this.newRoomContactPerson,
          capacity: this.newRoomCapacity ? parseInt(this.newRoomCapacity) : null,
          hasPhone: this.newRoomHasPhone,
          hasVideoConferencing: this.newRoomHasVideo,
          hasTv: this.newRoomHasTv,
          hasProjector: this.newRoomHasProjector,
          hasWhiteboard: this.newRoomHasWhiteboard,
          isWheelchairAccessible: this.newRoomWheelchairAccessible,
          storyId: storyId 
        })
      });
      // Reset form
      this.newRoomName = '';
      this.newRoomEmail = '';
      this.newRoomType = 'default';
      this.newRoomNumber = '';
      this.newRoomContactPerson = '';
      this.newRoomCapacity = null;
      this.newRoomHasPhone = false;
      this.newRoomHasVideo = false;
      this.newRoomHasTv = false;
      this.newRoomHasProjector = false;
      this.newRoomHasWhiteboard = false;
      this.newRoomWheelchairAccessible = false;
      this.selectedBuildingForRoom = null;
      this.selectedStory = null;
      this.loadRooms();
    },
    async deleteRoom(id) {
      await fetch(OC.generateUrl(`/apps/calendar_resource_management/admin/rooms/${id}`), {
        method: 'DELETE',
        headers: { 'requesttoken': OC.requestToken }
      });
      this.loadRooms();
    },
    async loadResources() {
      const res = await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/resources'), {
        headers: { 'requesttoken': OC.requestToken }
      });
      this.resources = await res.json();
    },
    async addResource() {
      if (!this.newResourceName) {
        alert('Bitte geben Sie einen Ressourcennamen ein!');
        return;
      }
      
      if (!this.selectedBuilding) {
        alert('Bitte wählen Sie zuerst ein Gebäude aus!');
        return;
      }
      
      // Extract the ID from the selected object
      const buildingId = typeof this.selectedBuilding === 'object' 
        ? this.selectedBuilding.id 
        : this.selectedBuilding;
        
      await fetch(OC.generateUrl('/apps/calendar_resource_management/admin/resources'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
        body: JSON.stringify({ 
          name: this.newResourceName, 
          email: this.newResourceEmail, 
          resourceType: this.newResourceType, 
          buildingId: buildingId 
        })
      });
      this.newResourceName = '';
      this.newResourceEmail = '';
      this.newResourceType = 'default';
      this.selectedBuilding = null;
      this.loadResources();
    },
    async deleteResource(id) {
      await fetch(OC.generateUrl(`/apps/calendar_resource_management/admin/resources/${id}`), {
        method: 'DELETE',
        headers: { 'requesttoken': OC.requestToken }
      });
      this.loadResources();
    },
    getBuildingName(id) {
      const building = this.buildings.find(b => b.id == id);
      return building ? building.name : 'Unbekannt';
    },
    getBuildingNameForRoom(storyId) {
      const story = this.stories.find(s => s.id == storyId);
      if (!story) return 'Unbekannt';
      const building = this.buildings.find(b => b.id == story.buildingId);
      return building ? building.name : 'Unbekannt';
    },
    getStoryName(id) {
      const story = this.stories.find(s => s.id == id);
      return story ? story.name : 'Unbekannt';
    }
  }
}
</script>

<style scoped>
.section {
  max-width: 1200px;
  padding: 20px;
}

.section h2 {
  margin-bottom: 30px;
  font-size: 24px;
  font-weight: 600;
}

/* FormGroup Spacing */
:deep(.nc-form-group) {
  margin-bottom: 40px;
}

:deep(.nc-form-group legend) {
  margin-bottom: 20px;
  font-size: 20px;
  font-weight: 700;
  padding-top: 30px;
  padding-inline: var(--form-element-label-offset);
}

/* Input Fields Layout */
:deep(.nc-text-field),
:deep(.nc-select) {
  margin-bottom: 15px;
  max-width: 500px;
  width: 100%;
}

:deep(.nc-select .select-wrapper) {
  max-width: 500px;
  width: 100%;
}

:deep(.nc-select .v-select) {
  width: 100% !important;
}

/* Custom Input Field Styling */
.input-field {
  margin-bottom: 15px;
  max-width: 500px;
  width: 100%;
}

.input-field label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--color-main-text);
  font-size: 14px;
}

.capacity-input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: 8px;
  font-size: 14px;
  background-color: var(--color-main-background);
  color: var(--color-main-text);
  transition: border-color 0.2s;
}

.capacity-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.capacity-input::placeholder {
  color: var(--color-text-maxcontrast);
}

/* Checkbox Group Styling */
.checkbox-group {
  margin-top: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: var(--color-background-hover);
  border-radius: 8px;
  max-width: 500px;
}

.checkbox-group h4 {
  margin-top: 0;
  margin-bottom: 12px;
  font-size: 16px;
  font-weight: 600;
}

.checkbox-group :deep(.checkbox-radio-switch) {
  margin-bottom: 8px;
}

/* Button Spacing */
:deep(.button-vue) {
  margin-top: 10px;
  margin-bottom: 20px;
}

/* Table Styling */
.table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  margin-top: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
}

.table th, .table td {
  border: 1px solid #e0e0e0;
  padding: 12px 16px;
  text-align: left;
}

.table th {
  background-color: var(--color-background-dark);
  font-weight: 600;
  color: var(--color-main-text);
}

.table tr:hover {
  background-color: var(--color-background-hover);
}

.table td {
  background-color: var(--color-main-background);
}

/* Form Grid Layout */
.form-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}

.form-row > * {
  flex: 1;
  min-width: 200px;
}
</style>

