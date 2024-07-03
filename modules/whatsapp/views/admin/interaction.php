<title>WhatsApp Cloud API Business Chat</title>
<?php init_head(); 
// Store CSRF token in session
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
?>
<div class="content" id="wrapper">
  <div id="app" class="interaction-container">
    <div class="flex flex-col md:flex-row h-screen">
      <!-- Sidebar -->
      <div class="w-full md:w-1/3 lg:w-1/4 xl:w-1/5 bg-gray-50 border-r border-gray-50 overflow-y-auto">
        <div class="p-4 sticky">
          <select v-model="selectedWaNo" @change="filterInteractions">
            <option value="*" :selected="selectedWaNo === '*'">All Chats</option>
            <option v-for="(interaction, index) in uniqueWaNos" :key="index" :value="interaction.wa_no">
              {{ interaction.wa_no }}
            </option>
          </select>
        </div>
        <div class="interaction-list">
          <div v-for="(interaction, index) in filteredInteractions" :key="interaction.id" @click="selectInteraction(interaction.id)" class="interaction-item cursor-pointer bg-white border-b border-gray-50 p-4 flex items-center justify-between">
            <div class="flex items-center">
              <div class="mr-3">
                <div class="w-10 h-10 flex items-center justify-center rounded-full" style="background-color:rgb(0 218 95);">
                  <p class="text-white font-bold text-base">{{ getAvatarInitials(interaction.name) }}</p>
                </div>
              </div>
              <div>
                <h5 class="mt-0 text-base font-semibold" style="word-wrap: break-word;">{{ interaction.name }}</h5>
                <span class="underline">{{ interaction.type }}</span>
                <p class="mb-0 text-gray-400 last-message">{{ interaction.last_message }}</p>
              </div>
              <div v-if="countUnreadMessages(interaction.id) > 0" class="ml-2"></div>
            </div>
            <div>
              <span>{{ formatTime(interaction.time_sent) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main interaction area -->
      <div class="w-full md:w-2/3 lg:w-3/4 xl:w-4/5 bg-gray-50 border-r border-gray-50 overflow-y-auto interaction-messages relative">
        <div class="p-4 border-b border-gray-50 bg-gray w-100 bg-white sticky top-0 z-10" v-if="selectedInteraction && typeof selectedInteraction === 'object'">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="w-10 h-10 flex items-center justify-center rounded-full mr-4" style="background-color:rgb(0 218 95);">
                <p class="text-white font-bold text-base">{{ getAvatarInitials(selectedInteraction.name) }}</p>
              </div>
              <div>
                <h5 class="mt-0 text-lg font-semibold">{{ selectedInteraction.name }}</h5>
                <p class="mb-0 text-sm text-gray-600">Phone: {{ selectedInteraction.receiver_id }}</p>
                <p class="mb-0 text-sm text-gray-600">From: {{ selectedInteraction.wa_no }}</p>
              </div>
            </div>
            <div>
              <p class="mb-0 text-sm text-gray-600" v-if="selectedInteraction.last_msg_time">{{ alertTime(selectedInteraction.last_msg_time) }}</p>
              <p class="mb-0 text-sm text-gray-600" v-else></p>
            </div>
          </div>
        </div>

<!-- Interaction messages -->
<div class="overflow-y-auto max-h-[90vh] p-4 bg-gray" v-if="selectedInteraction && selectedInteraction.messages">
  <template v-for="(message, index) in selectedInteraction.messages" :key="index">
    <div :class="[message.sender_id === selectedInteraction.wa_no ? 'flex justify-end mb-4' : 'flex mb-4', message.size, message]">
      <div :class="{'bg-green-100': message.sender_id === selectedInteraction.wa_no, 'bg-blue-50': message.sender_id !== selectedInteraction.wa_no}" class="rounded-lg p-3">
        <template v-if="message.type === 'text'">
          <p class="text-sm">{{ message.message }}</p>
        </template>
        <template v-else-if="message.type === 'image'">
          <img :src="message.asset_url" alt="Image" class="max-w-[200px] max-h-[112px] w-1/3 rounded-lg">
          <p class="text-sm mt-2" v-if="message.caption">{{ message.caption }}</p>
        </template>
        <template v-else-if="message.type === 'video'">
          <video :src="message.asset_url" controls class="max-w-[200px] max-h-[112px] w-1/3 rounded-lg"></video>
          <p class="text-sm mt-2" v-if="message.message">{{ message.message }}</p>
        </template>
        <template v-else-if="message.type === 'document'">
          <a :href="message.asset_url" target="_blank" class="text-blue-500">Download Document</a>
        </template>
        <template v-else-if="message.type === 'audio'">
          <audio :src="message.asset_url" controls class="max-w-[200px] max-h-[200px]"></audio>
          <p class="text-sm mt-2" v-if="message.message">{{ message.message }}</p>
        </template>
        <div class="flex items-center mt-2">
          <span class="text-xs text-gray-500">{{ message.time_sent }}</span>
          <span v-if="message.staff_id !== null" class="ml-auto text-xs text-gray-500">{{ message.staff_name }}</span>
          <span class="ml-2" v-if="message.sender_id === selectedInteraction.wa_no">
            <i v-if="message.status === 'sent'" class="fas fa-check text-gray-500" title="Sent"></i>
            <i v-else-if="message.status === 'delivered'" class="fas fa-check-double text-gray-500" title="Delivered"></i>
            <i v-else-if="message.status === 'read'" class="fas fa-check-double text-blue-500" title="Read"></i>
            <i v-else-if="message.status === 'failed'" class="fas fa-exclamation-circle text-red-500" title="Failed"></i>
            <i v-else-if="message.status === 'deleted'" class="fas fa-trash-circle text-red-500" title="Deleted"></i>
          </span>
        </div>
      </div>
    </div>
  </template>

<div class="p-4 bg-white">
  <form @submit.prevent="sendMessage" class="flex items-center justify-between">
    <!-- Attachment inputs -->
    <div class="flex items-center">
      <label for="imageAttachmentInput" class="mr-2 cursor-pointer">
        <span class="fas fa-image text-gray-500 hover:text-gray-700 rounded-full p-2"></span>
      </label>
      <input type="file" id="imageAttachmentInput" ref="imageAttachmentInput" @change="handleImageAttachmentChange" class="hidden">

      <label for="videoAttachmentInput" class="mr-2 cursor-pointer">
        <span class="fas fa-video text-gray-500 hover:text-gray-700 rounded-full p-2"></span>
      </label>
      <input type="file" id="videoAttachmentInput" ref="videoAttachmentInput" @change="handleVideoAttachmentChange" class="hidden">

      <label for="documentAttachmentInput" class="mr-2 cursor-pointer">
        <span class="fas fa-file text-gray-500 hover:text-gray-700 rounded-full p-2"></span>
      </label>
      <input type="file" id="documentAttachmentInput" ref="documentAttachmentInput" @change="handleDocumentAttachmentChange" class="hidden">

      <!-- Microphone button for audio recording -->
      <div class="attachment action-button">
        <button @click="toggleRecording" type="button" class="microphone-button">
          <span v-if="!recording" class="fas fa-microphone text-gray-500 hover:text-gray-700 rounded-full p-2"></span>
          <span v-else class="fas fa-stop text-gray-500 hover:text-gray-700 rounded-full p-2"></span>
        </button>
      </div>
    </div>

    <!-- Message input -->
    <div class="flex-1 ml-4">
      <input v-model="newMessage" type="text" class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:outline-none focus:border-blue-500" placeholder="Type a message..." aria-label="Type a message...">
    </div>

    <!-- Send button -->
    <div>
      <button v-if="showSendButton || audioBlob" type="submit" class="send-button">
        <span class="fas fa-paper-plane text-white rounded-full bg-blue-500 px-3 py-2 hover:bg-blue-600"></span>
      </button>
    </div>
  </form>
</div>

</div>
</div>
</div>

  </div>
</div>
<?php init_tail(); ?>
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/recorderjs/0.1.0/recorder.min.js" integrity="sha512-Dc8aBUPSsnAiEtyqTYZrldxDfs2FnS8cU7BVHIJ1m5atjKrtQCoPRIn3gsVbKm2qY8NwjpTVTnawoC4XBvEZiQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
new Vue({
  el: '#app',
  data() {
    return {
      interactions: [],
      selectedInteractionIndex: null,
      selectedInteraction: null,
      newMessage: '',
      imageAttachment: null,
      videoAttachment: null,
      documentAttachment: null,
      csrfToken: '<?php echo $csrfToken; ?>',
      recording: false,
      audioBlob: null,
      selectedWaNo: '*',
      filteredInteractions: []
    };
  },
  methods: {
    selectInteraction(id) {
      const index = this.interactions.findIndex(interaction => interaction.id === id);
      if (index !== -1) {
        this.selectedInteractionIndex = index;
        this.selectedInteraction = this.interactions[index];
        this.scrollToBottom();
      }
    },
    async sendMessage() {
      if (!this.selectedInteraction) return;
        const formData = new FormData();
  formData.append('id', this.selectedInteraction.id);
  formData.append('to', this.selectedInteraction.receiver_id);
  formData.append('csrf_token_name', this.csrfToken);

  if (this.newMessage.trim()) {
    formData.append('message', this.newMessage);
  }
  if (this.imageAttachment) {
    formData.append('image', this.imageAttachment);
  }
  if (this.videoAttachment) {
    formData.append('video', this.videoAttachment);
  }
  if (this.documentAttachment) {
    formData.append('document', this.documentAttachment);
  }
  if (this.audioBlob) {
    const audioBlob = new Blob([this.audioBlob], { type: 'audio/wav; codecs=opus' });
    formData.append('audio', audioBlob, 'audio.wav');
  }

  try {
    const response = await axios.post('<?php echo admin_url('whatsapp/webhook/send_message') ?>', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    this.newMessage = '';
    this.imageAttachment = null;
    this.videoAttachment = null;
    this.documentAttachment = null;
    this.audioBlob = null;
    this.filterInteractions();

  } catch (error) {
    console.error('Error:', error);
  }
},
clearMessage() {
  this.newMessage = '';
  this.attachment = null;
  this.audioBlob = null;
},
toggleAttachmentInput() {
  this.$refs.attachmentInput.click();
},
handleAttachmentChange(event) {
  const files = event.target.files;
  this.attachment = files[0];
},
async fetchInteractions() {
  try {
    const response = await fetch('<?php echo admin_url('whatsapp/interactions') ?>');
    const data = await response.json();
    this.interactions = data.interactions || [];
    this.filterInteractions();
    if (this.selectedInteraction) {
      this.selectInteraction(this.selectedInteraction.id);
    }
  } catch (error) {
    console.error('Error fetching interactions:', error);
  }
},
scrollToBottom() {
  this.$nextTick(() => {
    const $interactionMessages = $('.interaction-messages');
    if ($interactionMessages.length > 0) {
      $interactionMessages.scrollTop($interactionMessages[0].scrollHeight - $interactionMessages[0].clientHeight);
    }
  });
},
getAvatarInitials(name) {
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase();
},
countUnreadMessages(interactionId) {
  const interaction = this.interactions.find(interaction => interaction.id === interactionId);
  if (!interaction) return 0;

  return interaction.messages.reduce((count, message) => {
    return message.status === 'sent' ? count + 1 : count;
  }, 0);
},
async toggleRecording() {
  if (!this.recording) {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      this.recorder = new MediaRecorder(stream);
      let chunks = [];
      this.recorder.ondataavailable = e => chunks.push(e.data);
      this.recorder.onstop = () => {
        const blob = new Blob(chunks, { type: 'audio/wav' });
        this.audioBlob = blob;
        this.sendMessage();
      };
      this.recorder.start();
      this.recording = true;
    } catch (error) {
      console.error('Error accessing microphone:', error);
    }
  } else {
    this.recorder.stop();
    this.recording = false;
  }
},
closeConversation() {
  this.selectedInteraction = null;
},
handleImageAttachmentChange(event) {
  this.imageAttachment = event.target.files[0];
},
handleVideoAttachmentChange(event) {
  this.videoAttachment = event.target.files[0];
},
handleDocumentAttachmentChange(event) {
  this.documentAttachment = event.target.files[0];
},
formatTime(timestamp) {
  const currentDate = new Date();
  const messageDate = new Date(timestamp);
  const diffInMs = currentDate - messageDate;
  const diffInHours = diffInMs / (1000 * 60 * 60);

  if (diffInHours < 24) {
    const hour = messageDate.getHours();
    const minute = messageDate.getMinutes();
    const period = hour < 12 ? 'AM' : 'PM';
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minute < 10 ? '0' + minute : minute} ${period}`;
  } else {
    const day = messageDate.getDate();
    const month = messageDate.getMonth() + 1;
    const year = messageDate.getFullYear() % 100;
    return `${day}-${month < 10 ? '0' + month : month}-${year}`;
  }
},
alertTime(lastMsgTime) {
  if (lastMsgTime) {
    const currentDate = new Date();
    const messageDate = new Date(lastMsgTime);
    const diffInMs = currentDate - messageDate;
    const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
    const diffInMinutes = Math.floor((diffInMs % (1000 * 60 * 60)) / (1000 * 60));

    if (diffInHours < 24) {
      const remainingHours = 23 - diffInHours;
      const remainingMinutes = 60 - diffInMinutes;
      return `Reply within ${remainingHours} hours and ${remainingMinutes} minutes`;
    } else {
          return '';
        }
      } else {
        return '';
      }
    },
    filterInteractions() {
      if (this.selectedWaNo !== "*") {
        this.filteredInteractions = this.interactions.filter(interaction => interaction.wa_no === this.selectedWaNo);
      } else {
        this.filteredInteractions = this.interactions;
      }
    },
    markInteractionAsRead(interactionId) {
      const interaction = this.interactions.find(interaction => interaction.id === interactionId);
      if (interaction) {
        interaction.read = true;
      }

      fetch('<?php echo admin_url('whatsapp/webhook/mark_interaction_as_read') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ interaction_id: interactionId, csrf_token_name: this.csrfToken }),
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          console.log('Interaction marked as read successfully');
        } else {
          console.error('Failed to mark interaction as read:', data.error);
        }
      })
      .catch(error => {
        console.error('Error marking interaction as read:', error);
        if (interaction) {
          interaction.read = false;
        }
      });
    },
  },
  created() {
    this.fetchInteractions();
    setInterval(() => {
      this.fetchInteractions();
    }, 5000);
  },
  computed: {
    selectedInteraction() {
      return this.selectedInteractionIndex !== null ? this.interactions[this.selectedInteractionIndex] : null;
    },
    showSendButton() {
      return this.imageAttachment || this.videoAttachment || this.documentAttachment || this.newMessage.trim();
    },
    uniqueWaNos() {
      const uniqueWaNos = new Set();
      return this.interactions.filter(interaction => {
        if (!uniqueWaNos.has(interaction.wa_no)) {
          uniqueWaNos.add(interaction.wa_no);
          return true;
        }
        return false;
      });
    }
  },
});
</script>

