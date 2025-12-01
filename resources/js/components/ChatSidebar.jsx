import React, { useState, useEffect } from "react";
import axios from "axios";
import UserSelectionModal from "./UserSelectionModal.jsx";
import ParticipantsShowModal from "./ParticipantsShowModal.jsx";
import OtherUserShowModal from "./OtherUserShowModal.jsx";
import { FaICursor, FaPlus } from "react-icons/fa";
import ReactDOM from "react-dom";
import "bootstrap";


const ChatSidebar = ({ rootUrl, onSelectChat, messageCounts, lastChat, user }) => {
  const [chats, setChats] = useState([]);
  const [showUserModal, setShowUserModal] = useState(false);
  const [showParticipantModal, setShowParticipantModal] = useState(false);
  const [showOtherParticipantModal, setShowOtherParticipantModal] = useState(false);
  const [selectedChat, setSelectedChat] = useState('');
  const fetchChats = async () => {
    try {
      const res = await axios.get(`${rootUrl}/chats`);
      setChats(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchChats();
  }, []);

  return (
    <div className="chat-sidebar border-end" style={{ width: "300px" }}>
      <div className="sidebar-header d-flex justify-content-between align-items-center p-2 border-bottom">
        <h5>Chats</h5>
        <button
          className="btn btn-primary btn-sm"
          onClick={() => setShowUserModal(true)}
        >
          <FaPlus />
        </button>
      </div>

      <div className="chat-list" style={{ maxHeight: "calc(100vh - 60px)", overflowY: "auto" }}>
        {chats.map(chat => (
          <div
            key={chat.id}
            className="chat-item p-2 border-bottom"
            style={{ cursor: "pointer" }}
            onClick={() => onSelectChat(chat.id)}
          >
            <strong>{chat.is_group ? chat.name : chat.name} <span className="badge text-bg-success">{(messageCounts[chat.id] ? messageCounts[chat.id] : chat.unReadCount) ?? ''}</span></strong>
            {
              chat.is_group ?
              <span className="dropdown" onClick={(e) => e.stopPropagation()}>
                <button className="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">&#8942;</button>
                <ul className="dropdown-menu">
                  <li><a className="dropdown-item" onClick={() => { setShowParticipantModal(true); setSelectedChat(chat.id) }}>Show Users</a></li>
                  <li><a className="dropdown-item" onClick={() => { setShowOtherParticipantModal(true); setSelectedChat(chat.id) }}>Add Users</a></li>
                </ul>
              </span>
              : ''
            }
            <div className="last-message text-muted">{lastChat[chat.id] ? lastChat[chat.id] : chat.last_message}</div>
          </div>
        ))}
      </div>

      {showUserModal && ReactDOM.createPortal(
        <UserSelectionModal
          rootUrl={rootUrl}
          onClose={() => setShowUserModal(false)}
          onChatCreated={fetchChats}
        />,
        document.body
      )}
      {showParticipantModal && ReactDOM.createPortal(
        <ParticipantsShowModal
          rootUrl={rootUrl}
          onClose={() => setShowParticipantModal(false)}
          selectedChat={selectedChat}
        />,
        document.body
      )}
      {showOtherParticipantModal && ReactDOM.createPortal(
        <OtherUserShowModal
          rootUrl={rootUrl}
          onClose={() => setShowOtherParticipantModal(false)}
          selectedChat={selectedChat}
        />,
        document.body
      )}
    </div>
  );
};

export default ChatSidebar;
